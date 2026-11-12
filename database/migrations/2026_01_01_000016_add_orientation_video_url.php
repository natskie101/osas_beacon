<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Optional YouTube/Vimeo link for orientation modules.
        Schema::table('orientation_modules', function (Blueprint $table): void {
            $table->string('video_url', 500)->default('');
        });
    }

    public function down(): void
    {
        Schema::table('orientation_modules', function (Blueprint $table): void {
            $table->dropColumn('video_url');
        });
    }
};
