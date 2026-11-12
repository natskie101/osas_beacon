<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Track which dataset a generated report belongs to and the export
        // date range.
        Schema::table('reports', function (Blueprint $table): void {
            $table->string('report_type', 60)->default('');
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->dropColumn(['report_type', 'date_from', 'date_to']);
        });
    }
};
