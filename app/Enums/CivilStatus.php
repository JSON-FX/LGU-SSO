<?php

namespace App\Enums;

enum CivilStatus: string
{
    case Single = 'single';
    case Married = 'married';
    case Widowed = 'widowed';
    case Separated = 'separated';
    case Divorced = 'divorced';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
