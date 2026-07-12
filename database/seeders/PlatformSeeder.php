<?php
namespace Database\Seeders;
use App\Models\SmsSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder {
    public function run(): void {
        $email = env('PLATFORM_ADMIN_EMAIL', 'admin@voxsign.co.ug');
        $password = env('PLATFORM_ADMIN_PASSWORD', 'password1234');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'full_name' => env('PLATFORM_ADMIN_NAME', 'Platform Admin'),
                'status' => 'active',
                'is_platform' => true,
                'password' => $password,
            ]
        );

        // Keep platform flag and allow password rotation via env on re-seed.
        $user->forceFill([
            'is_platform' => true,
            'status' => 'active',
            'password' => $password,
        ])->save();

        SmsSetting::current();   // ensure the single settings row exists
    }
}
