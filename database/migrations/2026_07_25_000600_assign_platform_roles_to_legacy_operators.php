<?php

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-time: assign platform_admin to legacy is_platform users who have no platform role.
 * Runtime code must never auto-heal; this migration is the only bulk assignment path.
 */
return new class extends Migration
{
    public function up(): void
    {
        $roleId = Role::query()->where('key', 'platform_admin')->value('id');
        if (! $roleId) {
            return;
        }

        $users = User::query()
            ->where('is_platform', true)
            ->whereNull('deleted_at')
            ->get();

        foreach ($users as $user) {
            $hasPlatformRole = RoleAssignment::query()
                ->where('user_id', $user->id)
                ->whereNull('school_id')
                ->where('is_active', true)
                ->whereHas('role', fn ($q) => $q->where('scope', 'platform'))
                ->exists();

            if ($hasPlatformRole) {
                continue;
            }

            RoleAssignment::create([
                'user_id' => $user->id,
                'role_id' => $roleId,
                'school_id' => null,
                'is_active' => true,
                'assigned_by' => null,
            ]);
        }
    }

    public function down(): void
    {
        // Non-destructive: do not strip roles that may have been intentional.
    }
};
