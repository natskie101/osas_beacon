<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Widen users.status to include 'Archived'.
        //
        // On PostgreSQL an enum column is a varchar with an inline CHECK
        // constraint (Laravel's pgsql grammar), and ALTER COLUMN ... TYPE
        // cannot carry a replacement CHECK, so the constraint is swapped with
        // explicit DDL instead of ->change().
        $statusChecks = DB::select(
            "SELECT conname FROM pg_constraint"
            . " WHERE conrelid = 'users'::regclass"
            . " AND contype = 'c'"
            . " AND pg_get_expr(conbin, conrelid) LIKE '%Inactive%'",
        );

        foreach ($statusChecks as $constraint) {
            DB::statement('ALTER TABLE users DROP CONSTRAINT "' . $constraint->conname . '"');
        }

        DB::statement(
            "ALTER TABLE users ADD CONSTRAINT users_status_check"
            . " CHECK (status IN ('Active', 'Pending', 'Inactive', 'Archived'))",
        );

        // Remember the pre-archived status shown while an account is archived.
        Schema::table('users', function (Blueprint $table): void {
            $table->enum('archived_status', ['Active', 'Pending', 'Inactive'])->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('archived_status');
        });

        $statusChecks = DB::select(
            "SELECT conname FROM pg_constraint"
            . " WHERE conrelid = 'users'::regclass"
            . " AND contype = 'c'"
            . " AND pg_get_expr(conbin, conrelid) LIKE '%Inactive%'",
        );

        foreach ($statusChecks as $constraint) {
            DB::statement('ALTER TABLE users DROP CONSTRAINT "' . $constraint->conname . '"');
        }

        DB::statement(
            "ALTER TABLE users ADD CONSTRAINT users_status_check"
            . " CHECK (status IN ('Active', 'Pending', 'Inactive'))",
        );
    }
};
