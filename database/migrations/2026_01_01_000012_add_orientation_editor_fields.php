<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Orientation module editor fields: content type, verification and
        // media.
        Schema::table('orientation_modules', function (Blueprint $table): void {
            $table->string('content_type', 50)->default('Video Presentation');
            $table->string('verification_method', 80)->default('Quiz Assessment');
            $table->unsignedTinyInteger('passing_rate')->default(0);
            $table->date('compliance_deadline')->nullable();
            $table->unsignedBigInteger('media_file_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orientation_modules', function (Blueprint $table): void {
            $table->dropColumn([
                'content_type',
                'verification_method',
                'passing_rate',
                'compliance_deadline',
                'media_file_id',
            ]);
        });
    }
};
