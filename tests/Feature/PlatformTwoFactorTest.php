<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
