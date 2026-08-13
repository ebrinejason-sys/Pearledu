<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PlatformSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformSeederIdempotentTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_seeder_does_not_collide_when_admin_email_already_exists(): void
    {
        $this->seed(RoleSeeder::class);

        $email = (string) config('platform.admin_email');
        $password = (string) config('platform.admin_password');

        $otherPlatform = User::query()->create([
            'full_name' => 'Older Operator',
            'email' => 'older-operator@voxsign.test',
            'status' => 'active',
            'password' => $password,
        ]);
        $otherPlatform->forceFill(['is_platform' => true])->save();

        $named = User::query()->create([
            'full_name' => 'School Contact',
            'email' => $email,
            'status' => 'active',
            'password' => $password,
        ]);

        $this->seed(PlatformSeeder::class);
        $this->seed(PlatformSeeder::class);

        $named->refresh();
        $otherPlatform->refresh();

        $this->assertTrue($named->is_platform);
        $this->assertSame($email, $named->email);
        $this->assertSame('older-operator@voxsign.test', $otherPlatform->email);
        $this->assertTrue($otherPlatform->is_platform);
    }
}
