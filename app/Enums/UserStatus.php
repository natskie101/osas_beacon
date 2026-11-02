<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'Active';
    case Pending = 'Pending';
    case Inactive = 'Inactive';

    public function label(): string
    {
        return $this->value;
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'badge--active',
            self::Pending => 'badge--pending',
            self::Inactive => 'badge--inactive',
        };
    }
}
