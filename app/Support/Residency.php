<?php

namespace App\Support;

/** Day vs boarding placement used for fee structures and learner profiles. */
class Residency
{
    public const DAY = 'day';

    public const BOARDING = 'boarding';

    public const ANY = 'any';

    /** @return list<string> */
    public static function learnerKeys(): array
    {
        return [self::DAY, self::BOARDING];
    }

    /** @return list<string> */
    public static function structureKeys(): array
    {
        return [self::ANY, self::DAY, self::BOARDING];
    }

    public static function label(?string $value): string
    {
        return match ($value) {
            self::DAY => 'Day',
            self::BOARDING => 'Boarding',
            self::ANY => 'Day and boarding',
            default => 'Day',
        };
    }

    public static function normalize(?string $value): string
    {
        return in_array($value, self::learnerKeys(), true) ? $value : self::DAY;
    }
}
