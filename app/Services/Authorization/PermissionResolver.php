<?php
namespace App\Services\Authorization;
use App\Models\User;
/** Permissions = UNION of a user's active assignments in a school (never a single label). */
class PermissionResolver {
    public function resolve(User $user, int $schoolId): array {
        $map = config('permissions.roles', []);
        $keys = $user->activeAssignments()->where('school_id', $schoolId)
            ->with('role')->get()->pluck('role.key')->unique();
        $perms = [];
        foreach ($keys as $k) foreach ($map[$k] ?? [] as $p) $perms[$p] = true;
        return array_keys($perms);
    }
}
