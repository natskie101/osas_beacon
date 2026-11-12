<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OrientationReportService;
use App\Support\Exceptions\NotFoundHttpException;
use Illuminate\Http\Request;
use App\Support\Response;

final class ReportController extends Controller
{
    public function index(Request $request)
    {
        $actor = auth_user();
        $search = mb_strtolower((string) $request->string('search'));
        $status = (string) $request->string('status');
        $period = (string) $request->string('period');

        $modules = OrientationReportService::moduleProgress();
        $summary = OrientationReportService::summary();

        if ($search !== '') {
            $modules = array_values(array_filter(
                $modules,
                static fn (array $module): bool => str_contains(mb_strtolower((string) $module['title']), $search)
                    || str_contains(mb_strtolower((string) $module['public_id']), $search)
                    || str_contains(mb_strtolower((string) $module['category']), $search),
            ));
        }

        if ($status !== '') {
            $modules = array_values(array_filter(
                $modules,
                static fn (array $module): bool => strcasecmp((string) $module['compliance']['label'], $status) === 0,
            ));
        }

        if ($period === '30' || $period === '90') {
            $cutoff = date('Y-m-d H:i:s', strtotime('-' . $period . ' days'));

            $modules = array_values(array_filter(
                $modules,
                static fn (array $module): bool => (string) $module['updated_at'] >= $cutoff,
            ));
        }

        return view('admin/reports/index', [
            'title' => 'Reports',
            'pageTitle' => 'Reports',
            'pageSubtitle' => 'Generate and export official reports for the Student Orientation & Information Module.',
            'pageKey' => 'reports',
            'user' => $actor,
            'role' => $actor['role'],
            'modules' => $modules,
            'summary' => $summary,
            'filters' => [
                'search' => (string) $request->string('search'),
                'status' => $status,
                'period' => $period,
            ],
        ]);
    }

    public function moduleReport(Request $request, string $publicId)
    {
        $actor = auth_user();
        $module = OrientationReportService::module($publicId);

        if ($module === null) {
            throw new NotFoundHttpException('The requested orientation report could not be found.');
        }

        return view('admin/reports/module', [
            'title' => $module['title'],
            'pageTitle' => $module['title'],
            'pageSubtitle' => $module['public_id'] . ' · ' . $module['version'] . ' · ' . $module['category'],
            'pageKey' => 'reports',
            'user' => $actor,
            'role' => $actor['role'],
            'module' => $module,
            'completions' => OrientationReportService::completions((int) $module['id']),
        ]);
    }

    public function moduleCsv(Request $request, string $publicId)
    {
        $module = OrientationReportService::module($publicId);

        if ($module === null) {
            throw new NotFoundHttpException('The requested orientation report could not be found.');
        }

        $csv = $this->completionCsv($module);

        return Response::download($module['public_id'] . '-completions.csv', "\xEF\xBB\xBF" . $csv, 'text/csv; charset=UTF-8');
    }

    public function moduleExcel(Request $request, string $publicId)
    {
        $module = OrientationReportService::module($publicId);

        if ($module === null) {
            throw new NotFoundHttpException('The requested orientation report could not be found.');
        }

        $rows = OrientationReportService::completions((int) $module['id']);
        $html = '<table border="1"><thead><tr><th>Student ID</th><th>Name</th><th>Program</th><th>Year Level</th><th>Date Completed</th></tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>'
                . '<td>' . e((string) $row['student_id']) . '</td>'
                . '<td>' . e(trim((string) $row['first_name'] . ' ' . (string) $row['last_name'])) . '</td>'
                . '<td>' . e((string) $row['program']) . '</td>'
                . '<td>' . e((string) $row['year_level']) . '</td>'
                . '<td>' . e((string) $row['completed_at']) . '</td>'
                . '</tr>';
        }

        $html .= '</tbody></table>';

        return Response::download($module['public_id'] . '-completions.xls', $html, 'application/vnd.ms-excel');
    }

    /** @param array<string, mixed> $module */
    private function completionCsv(array $module): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['Student ID', 'Name', 'Program', 'Year Level', 'Date Completed']);

        foreach (OrientationReportService::completions((int) $module['id']) as $row) {
            fputcsv($stream, [
                $row['student_id'],
                trim((string) $row['first_name'] . ' ' . (string) $row['last_name']),
                $row['program'],
                $row['year_level'],
                $row['completed_at'],
            ]);
        }

        rewind($stream);
        $csv = (string) stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }
}
