<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Optional photo of a lost/found item.
        Schema::table('lost_found_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('image_file_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lost_found_items', function (Blueprint $table): void {
            $table->dropColumn('image_file_id');
        });
    }
};
