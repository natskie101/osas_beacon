<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Student self-registration profile fields.
        Schema::table('users', function (Blueprint $table): void {
            $table->string('middle_name', 100)->nullable();
            $table->unsignedTinyInteger('age')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['middle_name', 'age']);
        });
    }
};
