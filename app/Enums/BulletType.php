<?php

namespace App\Enums;

enum BulletType: string
{
    case Task = 'task';
    case Note = 'note';

    public function icon(): string
    {
        return match ($this) {
            self::Task => 'check-circle',
            self::Note => 'light-bulb',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Task => 'Task',
            self::Note => 'Note',
        };
    }
}
