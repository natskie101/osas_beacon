<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables whose user reference must survive a profile delete.
     *
     * @var list<string>
     */
    private const TABLES = [
        'orientation_progress',
        'event_attendance',
        'orientation_quiz_attempts',
        'notifications',
    ];

    public function up(): void
    {
        // Keep participation and orientation history after its user profile
        // is deleted: drop the cascade FK, allow NULL, re-add as SET NULL.
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['user_id']);
            });

            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });

            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['user_id']);
            });

            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
            });

            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }
};
