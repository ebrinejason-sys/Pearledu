<?php
namespace Database\Seeders;
use App\Models\SmsSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
class PlatformSeeder extends Seeder {
    public function run(): void {
        User::firstOrCreate(
            ['email' => 'admin@voxsign.co.ug'],
            ['full_name'=>'Platform Admin','status'=>'active','is_platform'=>true,'password'=>'password1234']
        );
        SmsSetting::current();   // ensure the single settings row exists
    }
}
