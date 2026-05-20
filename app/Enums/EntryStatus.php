<?php

namespace App\Enums;

enum EntryStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Migrated = 'migrated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Migrated => 'Migrated',
        };
    }
}
