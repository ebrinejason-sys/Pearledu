<?php
namespace Database\Seeders;
use App\Models\SmsSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder {
    public function run(): void {
        $email = (string) env('PLATFORM_ADMIN_EMAIL', 'admin@voxsign.co.ug');
        $name = (string) env('PLATFORM_ADMIN_NAME', 'Platform Admin');
        $password = (string) env('PLATFORM_ADMIN_PASSWORD', 'password1234');

        // One platform operator: update the existing row if present, else create.
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
            User::create([
                'full_name' => $name,
                'email' => $email,
                'status' => 'active',
                'is_platform' => true,
                'password' => $password,
            ]);
        }

        SmsSetting::current();   // ensure the single settings row exists
    }
}
