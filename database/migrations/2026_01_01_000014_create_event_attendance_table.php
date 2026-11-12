<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Student attendance records used by the participation tracking
        // ledger.
        Schema::create('event_attendance', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('event_code', 30)->default('');
            $table->string('event_name', 200)->default('');
            $table->dateTime('attended_at')->nullable();
            $table->unsignedTinyInteger('credit_hours')->default(0);
            $table->dateTime('created_at');
            $table->index('user_id');
            $table->index('event_code');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attendance');
    }
};
