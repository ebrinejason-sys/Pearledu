<?php

namespace App\Services\Authorization;

use App\Models\User;
use App\Services\Platform\ImpersonationService;

/** Permissions = UNION of a user's active assignments in a school (never a single label). */
class PermissionResolver
{
    public function __construct(private ImpersonationService $impersonation) {}

    public function resolve(User $user, int $schoolId): array
    {
        $map = config('permissions.roles', []);

        // An explicitly elevated, ticket-linked Platform Admin support session
        // may operate across the school app while still visibly imitating the requester.
        if ($this->impersonation->grantsFullSchoolAccess($user, $schoolId)) {
            return array_values(array_unique(array_merge(...array_values($map))));
        }

        $keys = $user->activeAssignments()->where('school_id', $schoolId)
            ->with('role')->get()->pluck('role.key')->unique();
        $perms = [];
        foreach ($keys as $k) {
            foreach ($map[$k] ?? [] as $p) {
                $perms[$p] = true;
            }
        }

        return array_keys($perms);
    }

    /** @return list<string> */
    public function resolvePlatform(User $user): array
    {
        if (! $user->isPlatformOperator()) {
            return [];
        }

        $roleKey = $user->platformRoleKey();
        if (! $roleKey) {
            return [];
        }

        $map = config('permissions.platform_roles', []);
        $listed = $map[$roleKey] ?? [];

        if (in_array('*', $listed, true)) {
            return ['*'];
        }

        return array_values(array_unique($listed));
    }
}
