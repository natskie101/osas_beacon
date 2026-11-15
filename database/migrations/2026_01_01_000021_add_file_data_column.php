<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table): void {
            if (!Schema::hasColumn('files', 'file_data')) {
                $table->text('file_data')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table): void {
            if (Schema::hasColumn('files', 'file_data')) {
                $table->dropColumn('file_data');
            }
        });
    }
};
