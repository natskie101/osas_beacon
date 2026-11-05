<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Coordinator = 'Coordinator';
    case Administrator = 'Administrator';
    case Student = 'Student';

    public function label(): string
    {
        return match ($this) {
            self::Coordinator => 'Coordinator',
            self::Administrator => 'Administrator',
            self::Student => 'Student',
        };
    }

    public function homePath(): string
    {
        return match ($this) {
            self::Student => '/student/dashboard',
            default => '/admin/dashboard',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Student;
    }

    public function isCoordinator(): bool
    {
        return $this === self::Coordinator;
    }

    public function isAdministrator(): bool
    {
        return $this === self::Administrator;
    }

    /** @return list<string> */
    public static function staffValues(): array
    {
        return [self::Coordinator->value, self::Administrator->value];
    }
}
