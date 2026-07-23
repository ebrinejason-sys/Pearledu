<?php

namespace App\Support;

/** Uganda district helpers for onboarding validation and pickers. */
class UgandaDistricts
{
    /** @return list<string> */
    public static function all(): array
    {
        return array_values(config('uganda.districts', []));
    }

    public static function isValid(?string $district): bool
    {
        if ($district === null || $district === '') {
            return false;
        }

        return in_array($district, self::all(), true);
    }

    /** @return list<string> */
    public static function optionsAllowing(?string $extra): array
    {
        $districts = self::all();
        if ($extra && ! in_array($extra, $districts, true)) {
            array_unshift($districts, $extra);
        }

        return $districts;
    }
}
