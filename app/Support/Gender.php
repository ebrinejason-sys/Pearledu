<?php

namespace App\Support;

/**
 * School demographic sex used for registers and oversight stats.
 */
class Gender
{
    public const MALE = 'male';

    public const FEMALE = 'female';

    /** @return list<string> */
    public static function keys(): array
    {
        return [self::MALE, self::FEMALE];
    }

    public static function label(?string $value): string
    {
        return match ($value) {
            self::MALE => 'Male',
            self::FEMALE => 'Female',
            default => 'Unspecified',
        };
    }
}
