<?php

namespace App\Enums;

enum ProjectRole: string
{
    case Owner  = 'owner';
    case Admin  = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Owner  => 'Owner',
            self::Admin  => 'Admin',
            self::Member => 'Member',
            self::Viewer => 'Viewer'
        };
    }
}
