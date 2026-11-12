<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remember_tokens', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->char('selector', 18)->unique();
            $table->char('validator_hash', 64);
            $table->dateTime('expires_at');
            $table->dateTime('created_at');
            $table->dateTime('last_used_at')->nullable();
            $table->index('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('password_resets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('email', 190);
            $table->char('token_hash', 64)->unique();
            $table->dateTime('expires_at');
            $table->dateTime('used_at')->nullable();
            $table->dateTime('created_at');
            $table->index('email');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('login_attempts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('email', 190);
            $table->string('ip_address', 45);
            $table->unsignedTinyInteger('successful')->default(0);
            $table->dateTime('attempted_at');
            $table->index(['email', 'ip_address', 'attempted_at']);
            $table->index('attempted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('remember_tokens');
    }
};
