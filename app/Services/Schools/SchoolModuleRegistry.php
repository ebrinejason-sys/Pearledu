<?php

namespace App\Services\Schools;

use App\Models\School;

class SchoolModuleRegistry
{
    /** @return array<string, string> */
    public function catalog(): array
    {
        return array_merge(config('modules.core', []), config('modules.optional', []));
    }

    /** @return array<string, bool> */
    public function defaults(): array
    {
        $modules = [];
        foreach (array_keys(config('modules.core', [])) as $key) {
            $modules[$key] = true;
        }
        foreach (array_keys(config('modules.optional', [])) as $key) {
            $modules[$key] = false;
        }

        return $modules;
    }

    public function enabled(School $school, string $module): bool
    {
        if ($module === 'emis') {
            return $school->emisEnabled();
        }
        if ($module === 'schoolpay') {
            return $school->schoolPayEnabled();
        }

        $stored = is_array($school->enabled_modules) ? $school->enabled_modules : [];
        if (array_key_exists($module, $stored)) {
            return (bool) $stored[$module];
        }

        return $this->defaults()[$module] ?? false;
    }

    /** @return array<string, bool> */
    public function snapshot(School $school): array
    {
        $snapshot = $this->defaults();
        foreach ($snapshot as $key => $on) {
            $snapshot[$key] = $this->enabled($school, $key);
        }

        return $snapshot;
    }
}
