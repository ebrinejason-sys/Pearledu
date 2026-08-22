<?php

namespace App\Support;

/** Bursar fee catalogue: tuition by residency, transport, or a custom type. */
class FeeKind
{
    public const TUITION = 'tuition';

    public const BOARDING = 'boarding';

    public const TRANSPORT = 'transport';

    public const OTHER = 'other';

    /** @return list<string> */
    public static function keys(): array
    {
        return [self::TUITION, self::BOARDING, self::TRANSPORT, self::OTHER];
    }

    public static function label(string $kind): string
    {
        return match ($kind) {
            self::TUITION => 'Tuition',
            self::BOARDING => 'Boarding',
            self::TRANSPORT => 'Transport / van',
            default => 'Other fee',
        };
    }

    /** Tuition/boarding must be saved per day or boarding. Other types may apply to both. */
    public static function requiresResidenceSplit(string $kind): bool
    {
        return in_array($kind, [self::TUITION, self::BOARDING], true);
    }
}
