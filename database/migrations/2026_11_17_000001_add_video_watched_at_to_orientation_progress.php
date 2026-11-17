<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Record when a student actually reached the end of a module video, so the
     * assessment / acknowledge button can stay locked until then.
     */
    public function up(): void
    {
        Schema::table('orientation_progress', function (Blueprint $table): void {
            $table->dateTime('video_watched_at')->nullable();
        });

        // Everyone already in the system keeps access: retroactively mark their
        // videos as watched so this new gate never locks an existing student out
        // of retaking an assessment they were already working on.
        DB::table('orientation_progress')
            ->whereNull('video_watched_at')
            ->update([
                'video_watched_at' => DB::raw('COALESCE(completed_at, last_viewed_at, created_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('orientation_progress', function (Blueprint $table): void {
            $table->dropColumn('video_watched_at');
        });
    }
};
