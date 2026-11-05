<?php

declare(strict_types=1);

namespace App\Models;

final class LostFoundClaim extends BaseModel
{
    protected static string $table = 'lost_found_claims';
    protected static string $prefix = 'CLM';
    protected static array $fillable = [
        'item_id', 'claimant_user_id', 'claimant_name', 'claimant_email', 'student_id',
        'proof', 'message', 'attachment', 'status', 'verified_by', 'verified_at',
    ];
    protected static array $searchable = ['public_id', 'claimant_name', 'claimant_email', 'student_id', 'proof'];
}
