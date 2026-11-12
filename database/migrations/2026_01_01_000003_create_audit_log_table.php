<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('actor_name', 150)->default('');
            $table->unsignedBigInteger('target_user_id')->nullable();
            $table->string('action', 60)->default('');
            $table->string('entity_type', 60)->default('');
            $table->string('entity_id', 60)->default('');
            $table->string('title', 180);
            $table->text('text')->nullable();
            $table->string('tone', 20)->default('brand');
            $table->string('icon', 40)->default('info');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->dateTime('created_at');
            $table->index('actor_user_id');
            $table->index('target_user_id');
            $table->index('action');
            $table->index('created_at');
            $table->foreign('actor_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('target_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
