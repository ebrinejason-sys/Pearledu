<?php

namespace Tests\Feature;

use App\Mail\Auth\ResetPasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_reset_email(): void
    {
        User::factory()->create(['email' => 'admin@voxsign.co.ug', 'status' => 'active']);
        Mail::fake();

        $response = $this->post(route('password.email'), ['email' => 'admin@voxsign.co.ug']);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Mail::assertSent(ResetPasswordMail::class, fn ($mail) => $mail->hasTo('admin@voxsign.co.ug'));
    }

    public function test_password_can_be_reset_via_email_link(): void
    {
        $user = User::factory()->create(['email' => 'admin@voxsign.co.ug', 'status' => 'active']);
        $token = Password::broker()->createToken($user);

        $response = $this->post(route('password.update'), [
            'email' => 'admin@voxsign.co.ug',
            'token' => $token,
            'password' => 'new-password-10',
            'password_confirmation' => 'new-password-10',
        ]);

        $response->assertRedirect('/login');
        $user->refresh();
        $this->assertTrue(Hash::check('new-password-10', $user->password));
        $this->assertSame('active', $user->status);
    }

    public function test_non_platform_login_is_unaffected_by_2fa(): void
    {
        $user = User::factory()->create([
            'email' => 'teacher@test.local',
            'password' => \Illuminate\Support\Facades\Hash::make('password1234'),
        ]);

        $response = $this->post('/login', [
            'identifier' => 'teacher@test.local',
            'password' => 'password1234',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertSessionMissing('2fa_pending_user_id');
    }

    public function test_platform_login_is_redirected_to_2fa_challenge_and_emails_otp(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $user = User::factory()->platform()->create([
            'email' => 'newadmin@test.local',
            'password' => \Illuminate\Support\Facades\Hash::make('password1234'),
        ]);

        $response = $this->post('/login', [
            'identifier' => 'newadmin@test.local',
            'password' => 'password1234',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login/2fa/challenge');
        $response->assertSessionHas('2fa_pending_user_id', $user->id);
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\Auth\TwoFactorEmailCodeMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_platform_login_is_redirected_to_2fa_challenge_when_enrolled(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $user = User::factory()->platform()->create([
            'email' => 'enrolledadmin@test.local',
            'password' => \Illuminate\Support\Facades\Hash::make('password1234'),
            'two_factor_secret' => 'ADUMMYSECRETKEYFORTESTS',
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->post('/login', [
            'identifier' => 'enrolledadmin@test.local',
            'password' => 'password1234',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login/2fa/challenge');
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\Auth\TwoFactorEmailCodeMail::class);
    }

    public function test_platform_login_regenerates_session_id_before_storing_pending_2fa_state(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $user = User::factory()->platform()->create([
            'email' => 'rotate@test.local',
            'password' => \Illuminate\Support\Facades\Hash::make('password1234'),
        ]);

        $sessionCookieName = config('session.cookie');

        // Establish a real, valid session ID first (as an attacker performing
        // session fixation would), so we have a genuine "before" value to
        // compare against rather than two independently-generated IDs.
        $priming = $this->get('/login');
        $originalSessionId = $priming->getCookie($sessionCookieName)->getValue();

        $response = $this->withCookie($sessionCookieName, $originalSessionId)->post('/login', [
            'identifier' => 'rotate@test.local',
            'password' => 'password1234',
        ]);

        $response->assertSessionHas('2fa_pending_user_id', $user->id);

        $newSessionId = $response->getCookie($sessionCookieName)->getValue();

        $this->assertNotSame(
            $originalSessionId,
            $newSessionId,
            'Session ID must rotate when entering the pending-2FA state, otherwise a fixated session ID would carry the pending-auth state through the 2FA window.'
        );
    }
}
