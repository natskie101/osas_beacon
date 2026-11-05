<?php
/** @var array<string, mixed> $report */
/** @var array{title:string,category:string,columns:list<array{key:string,label:string}>,rows:list<array<string,mixed>>,record_count:int} $dataset */
/** @var string $institution */
/** @var string $office */
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($report['title']) ?> — <?= e($report['public_id']) ?></title>
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/app-php.css') ?>">
    <style>
        body{background:#fff;color:#162033;font-family:Inter,system-ui,sans-serif;margin:0;padding:32px}
        .print-head{display:flex;justify-content:space-between;gap:24px;border-bottom:2px solid #162033;padding-bottom:18px;margin-bottom:24px}
        .print-head h1{font-size:22px;margin:0 0 4px}.print-head p{margin:2px 0;font-size:12px;color:#526071}
        .print-meta{text-align:right;font-size:12px;color:#526071}.print-meta strong{color:#162033}
        .print-table{width:100%;border-collapse:collapse;font-size:11px}.print-table th,.print-table td{border:1px solid #cfd8e3;padding:6px 7px;text-align:left;vertical-align:top}
        .print-table th{background:#eef3f8;font-size:10px;letter-spacing:.04em;text-transform:uppercase}
        .print-table tr:nth-child(even) td{background:#f8fafc}
        .print-footer{margin-top:22px;display:flex;justify-content:space-between;font-size:10px;color:#718096;border-top:1px solid #cfd8e3;padding-top:10px}
        @media print{body{padding:0}.print-table{font-size:9.5px}.no-print{display:none}}
        .no-print{margin-bottom:20px}.no-print button{background:#173b63;color:#fff;border:0;border-radius:6px;padding:9px 16px;font-weight:700;cursor:pointer}
    </style>
</head>
<body>
<div class="no-print"><button type="button" onclick="window.print()">Print / Save as PDF</button></div>
<div class="print-head">
    <div><h1><?= e($report['title']) ?></h1><p><?= e($institution) ?></p><p><?= e($office) ?></p><p><?= e($report['scope']) ?></p></div>
    <div class="print-meta"><div><strong><?= e($report['public_id']) ?></strong></div><div><?= e($report['period']) ?></div><div><?= number_format((int) $report['records']) ?> records</div><div>Generated <?= e(fmt_datetime($report['generated_at'])) ?></div><div>By <?= e($report['generated_by']) ?></div></div>
</div>
<table class="print-table">
    <thead><tr><?php foreach ($dataset['columns'] as $column): ?><th><?= e($column['label']) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php foreach ($dataset['rows'] as $row): ?>
        <tr><?php foreach ($dataset['columns'] as $column): ?><td><?= e($row[$column['key']] ?? '') ?></td><?php endforeach; ?></tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div class="print-footer"><span><?= e($institution) ?> · OSAS Portal</span><span><?= e($report['public_id']) ?></span></div>
</body>
</html>
