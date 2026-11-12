<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rotating infographics shown on the student dashboard (image bytes
        // live in files).
        Schema::create('dashboard_infographics', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('image_file_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('created_at');
            $table->index('image_file_id');
            $table->foreign('image_file_id')->references('id')->on('files')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_infographics');
    }
};
