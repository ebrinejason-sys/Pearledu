<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class PlatformTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function loginToPending(string $email = 'newadmin@test.local'): User
    {
        $user = User::factory()->platform()->create([
            'email' => $email,
            'password' => Hash::make('password1234'),
        ]);

        $this->post('/login', ['email' => $email, 'password' => 'password1234']);

        return $user;
    }

    public function test_setup_page_shows_qr_and_manual_key(): void
    {
        $this->loginToPending();

        $response = $this->get('/login/2fa/setup');

        $response->assertOk();
        $response->assertSee('<svg', false);
    }

    public function test_setup_confirms_with_correct_code_and_logs_in(): void
    {
        $user = $this->loginToPending();
        $this->get('/login/2fa/setup');
        $secret = session('2fa_setup_secret');
        $this->assertNotNull($secret, 'setup GET must seed a secret into session before POST is tested');

        $code = (new Google2FA())->getCurrentOtp($secret);

        $response = $this->post('/login/2fa/setup', ['code' => $code]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/login/2fa/recovery-codes');
        $user->refresh();
        $this->assertNotNull($user->two_factor_confirmed_at);
        $this->assertNotEmpty($user->two_factor_recovery_codes);
    }

    public function test_setup_rejects_wrong_code(): void
    {
        $this->loginToPending();
        $this->get('/login/2fa/setup');

        $response = $this->post('/login/2fa/setup', ['code' => '000000']);

        $this->assertGuest();
        $response->assertSessionHasErrors('code');
    }

    public function test_recovery_codes_page_is_reachable_after_login_completes(): void
    {
        // The user is authenticated by the time this page loads (store() calls Auth::login()
        // before redirecting here), so this route must NOT sit behind 'guest' middleware --
        // otherwise RedirectIfAuthenticated bounces the now-logged-in user away before they
        // ever see their one-time recovery codes.
        $this->loginToPending();
        $this->get('/login/2fa/setup');
        $secret = session('2fa_setup_secret');
        $code = (new Google2FA())->getCurrentOtp($secret);
        $this->post('/login/2fa/setup', ['code' => $code]);

        $response = $this->get('/login/2fa/recovery-codes');

        $response->assertOk();
        $response->assertSee('Save your recovery codes');
    }

    private function loginEnrolledToPending(): array
    {
        $secret = (new Google2FA())->generateSecretKey();
        $user = User::factory()->platform()->create([
            'email' => 'enrolled@test.local',
            'password' => Hash::make('password1234'),
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);

        $this->post('/login', ['email' => 'enrolled@test.local', 'password' => 'password1234']);

        return [$user, $secret];
    }

    public function test_challenge_succeeds_with_correct_totp(): void
    {
        [$user, $secret] = $this->loginEnrolledToPending();
        $code = (new Google2FA())->getCurrentOtp($secret);

        $response = $this->post('/login/2fa/challenge', ['code' => $code]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('platform.dashboard'));
    }

    public function test_challenge_fails_with_wrong_totp(): void
    {
        $this->loginEnrolledToPending();

        $response = $this->post('/login/2fa/challenge', ['code' => '000000']);

        $this->assertGuest();
        $response->assertSessionHasErrors('code');
    }

    public function test_email_otp_send_and_verify(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        [$user] = $this->loginEnrolledToPending();

        $this->post('/login/2fa/email');

        // The plaintext code only ever exists in the outgoing mail (the DB row stores a
        // hash), so capture it off the faked mailable's public property.
        $capturedCode = null;
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\Auth\TwoFactorEmailCodeMail::class, function ($mail) use ($user, &$capturedCode) {
            $capturedCode = $mail->code;
            return $mail->hasTo($user->email);
        });
        $this->assertNotNull($capturedCode);

        $response = $this->post('/login/2fa/challenge', ['code' => $capturedCode]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_recovery_code_redeems_once(): void
    {
        [$user, $secret] = $this->loginEnrolledToPending();
        $service = new \App\Services\Auth\TwoFactorService();
        $plainCodes = $service->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $service->hashRecoveryCodes($plainCodes)])->save();

        $response = $this->post('/login/2fa/challenge', ['recovery_code' => $plainCodes[0]]);
        $this->assertAuthenticatedAs($user);

        Auth::logout();
        $this->post('/login', ['email' => 'enrolled@test.local', 'password' => 'password1234']);
        $response2 = $this->post('/login/2fa/challenge', ['recovery_code' => $plainCodes[0]]);

        $this->assertGuest();
        $response2->assertSessionHasErrors('recovery_code');
    }
}
