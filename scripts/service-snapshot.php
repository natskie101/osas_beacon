<?php

declare(strict_types=1);

/**
 * Golden-output snapshot of every read-only service path (Phase 4b).
 *
 * Usage:
 *   php scripts/service-snapshot.php scripts/service-snapshot-before.json
 *
 * Run before and after a service refactor batch, then compare the two files
 * byte for byte with model-snapshot-diff.php. All entries are pure reads;
 * write paths are covered by model-write-probe.php and http-suite.php.
 *
 * Normalisation mirrors model-snapshot.php: the legacy raw-PDO wrapper
 * (App\Support\DB, removed in Phase 4c) could return rows carrying duplicate
 * integer keys, so those artifacts are stripped and associative arrays are
 * key-sorted, keeping snapshots comparable across the query-builder
 * conversion.
 */

use App\Services\AudienceService;
use App\Services\OrientationReportService;
use App\Services\ReportService;
use App\Services\TechnicalAdminOverview;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

/** Recursively drop PDO FETCH_BOTH integer artifacts and stabilise key order. */
function normalize_tree(mixed $value): mixed
{
    if (!is_array($value)) {
        return $value;
    }

    $hasString = false;
    $hasInt = false;

    foreach ($value as $key => $_) {
        if (is_int($key)) {
            $hasInt = true;
        } else {
            $hasString = true;
        }
    }

    $out = [];

    foreach ($value as $key => $item) {
        if ($hasString && $hasInt && is_int($key)) {
            continue;
        }

        $out[$key] = normalize_tree($item);
    }

    $allIntKeys = true;

    foreach (array_keys($out) as $key) {
        if (!is_int($key)) {
            $allIntKeys = false;
            break;
        }
    }

    if (!$allIntKeys) {
        ksort($out);
    }

    return $out;
}

$snapshot = [];

$add = static function (string $key, callable $fn) use (&$snapshot): void {
    try {
        $snapshot[$key] = $fn();
    } catch (Throwable $exception) {
        $snapshot[$key] = 'EXCEPTION ' . get_class($exception) . ': ' . $exception->getMessage();
    }
};

// ---------------------------------------------------------------------------
// TechnicalAdminOverview
// ---------------------------------------------------------------------------

$add('TechnicalAdminOverview.data', static fn (): mixed => TechnicalAdminOverview::data());

// ---------------------------------------------------------------------------
// OrientationReportService
// ---------------------------------------------------------------------------

$add('OrientationReportService.studentCount', static fn (): mixed => OrientationReportService::studentCount());
$add('OrientationReportService.summary', static fn (): mixed => OrientationReportService::summary());
$add('OrientationReportService.moduleProgress', static fn (): mixed => OrientationReportService::moduleProgress());
$add(
    'OrientationReportService.quizMetrics',
    static fn (): mixed => OrientationReportService::quizMetrics(512, OrientationReportService::studentCount()),
);
$add('OrientationReportService.quizMetrics-no-quiz', static fn (): mixed => OrientationReportService::quizMetrics(999999, 3));
$add('OrientationReportService.module', static fn (): mixed => OrientationReportService::module('ORIENT-2026-001'));
$add('OrientationReportService.module-miss', static fn (): mixed => OrientationReportService::module('ORIENT-0000-000'));
$add('OrientationReportService.completions', static fn (): mixed => OrientationReportService::completions(512));
$add('OrientationReportService.completions-empty', static fn (): mixed => OrientationReportService::completions(999999));

// ---------------------------------------------------------------------------
// AudienceService
// ---------------------------------------------------------------------------

$audienceVariants = [
    'all-students' => 'All Students',
    'all-portals' => 'All Student Portals',
    'freshmen' => 'Incoming Freshmen',
    'freshmen-padded' => '  Incoming Freshmen  ',
    'graduating' => 'Graduating Students',
    'officers' => 'Student Officers',
    'staff' => 'Faculty & Staff',
    'blank' => '',
    'unknown' => 'Something Else',
];

foreach ($audienceVariants as $label => $audience) {
    $add(
        "AudienceService.recipients-$label",
        static fn (): mixed => AudienceService::recipients($audience),
    );
}

$add('AudienceService.recipientById', static fn (): mixed => AudienceService::recipientById(1));
$add('AudienceService.recipientById-miss', static fn (): mixed => AudienceService::recipientById(987654));
$add('AudienceService.label-graduating', static fn (): mixed => AudienceService::label('Graduating Students'));
$add('AudienceService.label-unknown', static fn (): mixed => AudienceService::label('zzz'));

// ---------------------------------------------------------------------------
// ReportService: every type x date-filter variant.
// ---------------------------------------------------------------------------

$add('ReportService.types', static fn (): mixed => ReportService::types());

foreach (array_keys(ReportService::types()) as $type) {
    $add("ReportService.dataset-$type", static fn (): mixed => ReportService::dataset($type));
    $add("ReportService.dataset-$type-from", static fn (): mixed => ReportService::dataset($type, '2026-01-01'));
    $add("ReportService.dataset-$type-to", static fn (): mixed => ReportService::dataset($type, null, '2026-06-30'));
    $add(
        "ReportService.dataset-$type-range",
        static fn (): mixed => ReportService::dataset($type, '2026-01-01', '2026-06-30'),
    );
}

$add('ReportService.dataset-unknown-type', static fn (): mixed => ReportService::dataset('does-not-exist'));

// ---------------------------------------------------------------------------

$out = $argv[1] ?? ($root . '/scripts/service-snapshot.json');
$json = json_encode(
    normalize_tree($snapshot),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
);

if ($json === false) {
    fwrite(STDERR, 'json_encode failed: ' . json_last_error_msg() . PHP_EOL);
    exit(1);
}

file_put_contents($out, $json . "\n");
echo 'Wrote ', $out, ' (', count($snapshot), ' entries)', PHP_EOL;
