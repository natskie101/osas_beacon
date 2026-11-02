<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\AuditLogger;
use App\Services\ReportService;
use App\Support\Auth;
use App\Support\DB;
use App\Support\Exceptions\NotFoundHttpException;
use App\Support\Exceptions\ValidationException;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;
use App\Support\View;

final class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = Auth::user();
        $filters = [
            'search' => $request->string('search'),
            'status' => $request->string('status'),
        ];
        $pagination = Report::paginate(
            $filters,
            max(1, (int) $request->input('page', 1)),
            10,
            ['Completed', 'Processing', 'Draft'],
            'newest',
        );

        $counts = DB::first(
            'SELECT COUNT(*) AS total,
                    SUM(status = "Completed") AS completed,
                    SUM(status = "Processing") AS processing,
                    SUM(status = "Draft") AS draft,
                    COALESCE(SUM(records), 0) AS records
             FROM reports',
        );

        return $this->page('layouts.app', 'admin/reports/index', [
            'title' => 'Reports',
            'pageTitle' => config('navigation.meta.admin.reports.title'),
            'pageSubtitle' => config('navigation.meta.admin.reports.subtitle'),
            'pageKey' => 'reports',
            'user' => $actor,
            'role' => $actor['role'],
            'reports' => $pagination['data'],
            'pagination' => $pagination,
            'filters' => $filters,
            'counts' => array_map('intval', $counts ?? []),
            'types' => ReportService::types(),
        ]);
    }

    public function generate(Request $request): Response
    {
        $actor = Auth::user();
        $types = ReportService::types();
        $data = $this->validate($request, [
            'report_type' => 'required|string|max:60',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        if (!isset($types[$data['report_type']])) {
            throw new ValidationException(['report_type' => 'Choose a valid report type.'], $request->all());
        }

        $from = !empty($data['date_from']) ? (string) $data['date_from'] : null;
        $to = !empty($data['date_to']) ? (string) $data['date_to'] : null;

        if ($from !== null && $to !== null && $from > $to) {
            throw new ValidationException(['date_to' => 'The end date must be after the start date.'], $request->all());
        }

        $definition = $types[$data['report_type']];
        $dataset = ReportService::dataset($data['report_type'], $from, $to);
        $period = $from !== null || $to !== null
            ? ($from ?: 'Beginning') . ' to ' . ($to ?: 'Today')
            : 'All records';
        $id = Report::create([
            'title' => $definition['label'] . ' Report',
            'category' => $definition['category'],
            'report_type' => $data['report_type'],
            'period' => $period,
            'date_from' => $from,
            'date_to' => $to,
            'status' => 'Completed',
            'format' => 'CSV',
            'records' => $dataset['record_count'],
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => trim((string) $actor['first_name'] . ' ' . (string) $actor['last_name']),
            'scope' => (string) config('app.institution.name', 'Southway College of Technology'),
            'created_by' => (int) $actor['id'],
        ]);
        $report = Report::find($id);

        AuditLogger::log('report.generated', 'Report Generated', $report['title'] . ' (' . $report['public_id'] . ') was generated with ' . $report['records'] . ' records.', ['entity_type' => 'report', 'entity_id' => (string) $report['public_id'], 'tone' => 'blue', 'icon' => 'fileText']);
        Session::flash('status', 'Report generated. You can download the CSV or print the report.');

        return Response::redirect(url('/admin/reports/' . $report['public_id']));
    }

    public function show(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $report = $this->find($publicId);
        $type = $this->typeFor($report);
        $dataset = ReportService::dataset($type, $report['date_from'] ?? null, $report['date_to'] ?? null);

        return $this->page('layouts.app', 'admin/reports/show', [
            'title' => $report['title'],
            'pageTitle' => $report['title'],
            'pageSubtitle' => $report['public_id'] . ' · ' . $report['period'] . ' · ' . $report['records'] . ' records',
            'pageKey' => 'reports',
            'user' => $actor,
            'role' => $actor['role'],
            'report' => $report,
            'dataset' => $dataset,
            'typeLabel' => ReportService::types()[$type]['label'] ?? $report['category'],
        ]);
    }

    public function csv(Request $request, string $publicId): Response
    {
        $report = $this->find($publicId);
        $dataset = ReportService::dataset($this->typeFor($report), $report['date_from'] ?? null, $report['date_to'] ?? null);
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            return Response::html('Unable to build the CSV export.', 500);
        }

        fputcsv($stream, array_map(static fn (array $column): string => $column['label'], $dataset['columns']));

        foreach ($dataset['rows'] as $row) {
            $line = [];

            foreach ($dataset['columns'] as $column) {
                $value = $row[$column['key']] ?? '';
                $line[] = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            fputcsv($stream, $line);
        }

        rewind($stream);
        $csv = (string) stream_get_contents($stream);
        fclose($stream);
        $csv = "\xEF\xBB\xBF" . $csv;

        return Response::download($report['public_id'] . '.csv', $csv, 'text/csv; charset=UTF-8');
    }

    public function print(Request $request, string $publicId): Response
    {
        $report = $this->find($publicId);
        $dataset = ReportService::dataset($this->typeFor($report), $report['date_from'] ?? null, $report['date_to'] ?? null);

        return Response::html(View::render('admin/reports/print', [
            'report' => $report,
            'dataset' => $dataset,
            'institution' => (string) config('app.institution.name', 'Southway College of Technology'),
            'office' => 'Office of Student Affairs and Services',
        ]));
    }

    /** @return array<string, mixed> */
    private function find(string $publicId): array
    {
        $report = Report::findByPublicId($publicId);

        if ($report === null) {
            throw new NotFoundHttpException('The requested report could not be found.');
        }

        return $report;
    }

    /** @param array<string, mixed> $report */
    private function typeFor(array $report): string
    {
        $type = (string) ($report['report_type'] ?? '');

        if ($type !== '' && isset(ReportService::types()[$type])) {
            return $type;
        }

        return match ((string) $report['category']) {
            'User Management' => 'users',
            'Announcements' => 'announcements',
            'Rules & Regulations' => 'regulations',
            'Orientation Content' => 'orientation',
            'Events & Activities' => 'events',
            'Lost & Found' => 'lost_found',
            'Document Requests' => 'document_requests',
            'Feedback & Support' => 'support',
            'Broadcasts' => 'broadcasts',
            'Viewing & Tracking' => 'tracking',
            'User Activity' => 'activity',
            default => 'announcements',
        };
    }
}
