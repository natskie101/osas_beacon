<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Regulation severity rating used by the policy editor.
        Schema::table('regulations', function (Blueprint $table): void {
            $table->string('severity', 50)->default('');
        });
    }

    public function down(): void
    {
        Schema::table('regulations', function (Blueprint $table): void {
            $table->dropColumn('severity');
        });
    }
};
