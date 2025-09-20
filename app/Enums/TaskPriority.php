<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Low    = 'low';
    case Normal = 'normal';
    case High   = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low    => 'Low',
            self::Normal => 'Normal',
            self::High   => 'High'
        };
    }
}
