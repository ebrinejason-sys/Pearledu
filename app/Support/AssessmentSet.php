<?php

namespace App\Support;

/** Examination set kinds shown on class-teacher and teacher dashboards. */
class AssessmentSet
{
    public const BOT = 'bot';

    public const MOT = 'mot';

    public const EOT = 'eot';

    public const CUSTOM = 'custom';

    /** @return list<string> */
    public static function keys(): array
    {
        return [self::BOT, self::MOT, self::EOT, self::CUSTOM];
    }

    public static function label(?string $kind, ?string $name = null): string
    {
        return match ($kind) {
            self::BOT => 'Beginning of term (BOT)',
            self::MOT => 'Mid of term (MOT)',
            self::EOT => 'End of term (EOT)',
            default => $name !== null && $name !== '' ? $name : 'Custom test',
        };
    }

    public static function short(?string $kind, ?string $name = null): string
    {
        return match ($kind) {
            self::BOT => 'BOT',
            self::MOT => 'MOT',
            self::EOT => 'EOT',
            default => $name !== null && $name !== '' ? $name : 'Custom',
        };
    }

    public static function defaultName(string $kind, ?string $name = null): string
    {
        if ($kind === self::CUSTOM) {
            return filled($name) ? (string) $name : 'Custom test';
        }

        return filled($name) ? (string) $name : self::short($kind);
    }
}
