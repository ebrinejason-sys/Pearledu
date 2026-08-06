<?php

namespace Tests\Support;

use App\Http\Middleware\RequireRecentPlatformAuth;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Database\Seeders\RoleSeeder;

trait ActsAsPlatformOperator
{
    protected function ensurePlatformAdminRole(User $user, string $roleKey = 'platform_admin'): void
    {
        app(TenantContext::class)->forPlatform();

        if (! Role::where('key', $roleKey)->exists()) {
            $this->seed(RoleSeeder::class);
        }

        $user->forceFill(['is_platform' => true, 'status' => 'active'])->save();

        $roleId = Role::where('key', $roleKey)->value('id');
        RoleAssignment::query()
            ->where('user_id', $user->id)
            ->whereNull('school_id')
            ->where('is_active', true)
            ->update(['is_active' => false, 'ends_on' => now()]);

        RoleAssignment::create([
            'user_id' => $user->id,
            'role_id' => $roleId,
            'school_id' => null,
            'is_active' => true,
            'assigned_by' => null,
        ]);
    }

    protected function withRecentPlatformAuth(): static
    {
        session([RequireRecentPlatformAuth::SESSION_KEY => time()]);

        return $this;
    }
}
