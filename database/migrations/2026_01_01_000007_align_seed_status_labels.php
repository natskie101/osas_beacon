<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // No-op: the status enum values on regulations, lost_found_items,
        // lost_found_claims, document_requests and broadcasts were originally
        // re-typed here to match the static portal seed labels. The content
        // module migration already creates them with exactly those values.
    }

    public function down(): void
    {
        // Nothing to revert: the value lists never differed in this schema.
    }
};
