<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // BaseModel-managed claims need the same timestamp pair as other
        // records.
        Schema::table('lost_found_claims', function (Blueprint $table): void {
            $table->dateTime('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::table('lost_found_claims', function (Blueprint $table): void {
            $table->dropColumn('updated_at');
        });
    }
};
