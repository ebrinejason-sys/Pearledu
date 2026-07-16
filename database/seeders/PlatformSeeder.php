<?php
namespace Database\Seeders;
use App\Models\SmsSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class PlatformSeeder extends Seeder {
    public function run(): void {
        $email = (string) env('PLATFORM_ADMIN_EMAIL', 'admin@voxsign.co.ug');
        $name = (string) env('PLATFORM_ADMIN_NAME', 'Platform Admin');
        $password = (string) env('PLATFORM_ADMIN_PASSWORD', '');

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

        SmsSetting::current();   // ensure the single settings row exists
    }
}
