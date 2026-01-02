<?php

namespace App\Enums;

enum AppRole: string
{
    case Guest = 'guest';
    case Standard = 'standard';
    case Administrator = 'administrator';
    case SuperAdministrator = 'super_administrator';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
