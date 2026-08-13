<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\SmsSetting;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use RuntimeException;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        // Platform role assignments have school_id = null and are protected by
        // RLS, so seeding must explicitly use the platform policy branch.
        app(TenantContext::class)->forPlatform();

        // Use config() so this works after `php artisan config:cache` (env() is empty then).
        $email = (string) config('platform.admin_email', 'admin@voxsign.co.ug');
        $name = (string) config('platform.admin_name', 'Platform Admin');
        $password = (string) config('platform.admin_password', '');

        if ($password === '') {
            throw new RuntimeException(
                'PLATFORM_ADMIN_PASSWORD must be set in the environment before seeding. No default password is allowed.'
            );
        }

        // Prefer the configured email so a second seed never steals that address
        // from an existing school user onto a different platform row.
        $emailUser = User::withTrashed()->whereRaw('lower(email) = lower(?)', [$email])->first();
        $platformUser = User::where('is_platform', true)->orderBy('id')->first();

        if ($emailUser) {
            if ($emailUser->trashed()) {
                $emailUser->restore();
            }
            $emailUser->forceFill([
                'full_name' => $name,
                'email' => $email,
                'status' => 'active',
                'is_platform' => true,
                'password' => $password,
            ])->save();
            $user = $emailUser;
        } elseif ($platformUser) {
            $platformUser->forceFill([
                'full_name' => $name,
                'email' => $email,
                'status' => 'active',
                'is_platform' => true,
                'password' => $password,
            ])->save();
            $user = $platformUser;
        } else {
            $user = new User([
                'full_name' => $name,
                'email' => $email,
                'status' => 'active',
                'password' => $password,
            ]);
            $user->forceFill(['is_platform' => true])->save();
        }

        $roleId = Role::where('key', 'platform_admin')->value('id');
        if ($roleId) {
            RoleAssignment::firstOrCreate([
                'user_id' => $user->id,
                'role_id' => $roleId,
                'school_id' => null,
                'is_active' => true,
            ], ['assigned_by' => null]);
        }

        SmsSetting::current();   // ensure the single settings row exists
    }
}
