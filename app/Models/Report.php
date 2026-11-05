<?php

declare(strict_types=1);

namespace App\Models;

final class Report extends BaseModel
{
    protected static string $table = 'reports';
    protected static string $prefix = 'REP';
    protected static array $fillable = [
        'title', 'category', 'report_type', 'period', 'date_from', 'date_to', 'status', 'format',
        'records', 'generated_at', 'generated_by', 'scope', 'created_by',
    ];
    protected static array $searchable = ['public_id', 'title', 'category', 'period', 'scope', 'generated_by'];
}
