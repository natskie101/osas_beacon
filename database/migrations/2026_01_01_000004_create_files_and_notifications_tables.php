<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Uploaded files are stored outside the document root and streamed
        // through an authorised controller.
        Schema::create('files', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('owner_user_id')->nullable();
            $table->string('entity_type', 60)->default('');
            $table->string('entity_id', 60)->default('');
            $table->string('original_name', 255);
            $table->string('stored_name', 255);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->char('checksum', 64)->nullable();
            $table->dateTime('created_at');
            $table->index('owner_user_id');
            $table->index(['entity_type', 'entity_id']);
            $table->foreign('owner_user_id')->references('id')->on('users')->onDelete('set null');
        });

        // Per-user in-app notifications (bell).
        Schema::create('notifications', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('title', 180);
            $table->text('message')->nullable();
            $table->string('type', 40)->default('info');
            $table->string('entity_type', 60)->default('');
            $table->string('entity_id', 60)->default('');
            $table->dateTime('read_at')->nullable();
            $table->dateTime('created_at');
            $table->index(['user_id', 'read_at']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('files');
    }
};
