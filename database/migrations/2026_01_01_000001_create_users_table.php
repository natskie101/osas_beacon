<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('public_id', 20)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 190)->unique();
            $table->string('username', 100)->unique();
            $table->string('password_hash', 255);
            $table->enum('role', ['Coordinator', 'Administrator', 'Student'])->default('Student');
            $table->string('student_id', 30)->nullable();
            $table->string('department', 150)->nullable();
            $table->string('program', 150)->nullable();
            $table->string('year_level', 30)->nullable();
            $table->string('access_level', 150)->nullable();
            $table->enum('account_tier', ['Student Tier', 'Admin Tier'])->default('Student Tier');
            $table->enum('status', ['Active', 'Pending', 'Inactive'])->default('Pending');
            $table->string('phone', 30)->nullable();
            $table->unsignedBigInteger('school_id_file_id')->nullable();
            $table->dateTime('email_verified_at')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->dateTime('requested_at')->nullable();
            $table->index('role');
            $table->index('status');
            $table->index('last_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
