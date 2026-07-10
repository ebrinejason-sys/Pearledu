<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_platform_login_is_unaffected_by_2fa(): void
    {
        $user = User::factory()->create([
            'email' => 'teacher@test.local',
            'password' => \Illuminate\Support\Facades\Hash::make('password1234'),
        ]);

        $response = $this->post('/login', [
            'email' => 'teacher@test.local',
            'password' => 'password1234',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertSessionMissing('2fa_pending_user_id');
    }

    public function test_platform_login_is_redirected_to_2fa_setup_when_unenrolled(): void
    {
        $user = User::factory()->platform()->create([
            'email' => 'newadmin@test.local',
            'password' => \Illuminate\Support\Facades\Hash::make('password1234'),
        ]);

        $response = $this->post('/login', [
            'email' => 'newadmin@test.local',
            'password' => 'password1234',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login/2fa/setup');
        $response->assertSessionHas('2fa_pending_user_id', $user->id);
    }

    public function test_platform_login_is_redirected_to_2fa_challenge_when_enrolled(): void
    {
        $user = User::factory()->platform()->create([
            'email' => 'enrolledadmin@test.local',
            'password' => \Illuminate\Support\Facades\Hash::make('password1234'),
            'two_factor_secret' => 'ADUMMYSECRETKEYFORTESTS',
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'enrolledadmin@test.local',
            'password' => 'password1234',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login/2fa/challenge');
    }
}
