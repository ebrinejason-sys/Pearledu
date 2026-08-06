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

        // One platform operator: update the existing row if present, else create.
        // is_platform is not mass-assignable — always set via forceFill.
        $user = User::where('is_platform', true)->first()
            ?? User::whereRaw('lower(email) = lower(?)', [$email])->first();

        if ($user) {
            $user->forceFill([
                'full_name' => $name,
                'email' => $email,
                'status' => 'active',
                'is_platform' => true,
                'password' => $password,
            ])->save();
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
