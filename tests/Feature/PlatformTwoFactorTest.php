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
        \Illuminate\Support\Facades\Mail::fake();

        $user = User::factory()->platform()->create([
            'email' => $email,
            'password' => Hash::make('password1234'),
        ]);

        $this->post('/login', ['identifier' => $email, 'password' => 'password1234']);

        return $user;
    }

    private function verifyEmailToSetup(User $user): string
    {
        $capturedCode = null;
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\Auth\TwoFactorEmailCodeMail::class, function ($mail) use ($user, &$capturedCode) {
            $capturedCode = $mail->code;

            return $mail->hasTo($user->email);
        });

        $this->post('/login/2fa/challenge', ['code' => $capturedCode])
            ->assertRedirect('/login/2fa/setup');

        return $capturedCode;
    }

    public function test_unenrolled_platform_admin_can_continue_after_email_otp(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $user = User::factory()->platform()->create([
            'email' => 'emailonly@test.local',
            'password' => Hash::make('password1234'),
        ]);

        $this->post('/login', ['identifier' => 'emailonly@test.local', 'password' => 'password1234'])
            ->assertRedirect('/login/2fa/challenge');

        $this->verifyEmailToSetup($user);

        $this->post('/login/2fa/setup/skip')
            ->assertRedirect(route('platform.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_setup_is_blocked_before_email_otp(): void
    {
        $this->loginToPending();

        $this->get('/login/2fa/setup')->assertForbidden();
        $this->post('/login/2fa/setup', ['code' => '123456'])->assertForbidden();
    }

    public function test_setup_page_shows_qr_and_manual_key(): void
    {
        $user = $this->loginToPending();
        $this->verifyEmailToSetup($user);

        $response = $this->get('/login/2fa/setup');

        $response->assertOk();
        $response->assertSee('<svg', false);
    }

    public function test_setup_confirms_with_correct_code_and_logs_in(): void
    {
        $user = $this->loginToPending();
        $this->verifyEmailToSetup($user);
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
        $user = $this->loginToPending();
        $this->verifyEmailToSetup($user);
        $this->get('/login/2fa/setup');

        $response = $this->post('/login/2fa/setup', ['code' => '000000']);

        $this->assertGuest();
        $response->assertSessionHasErrors('code');
    }

    public function test_recovery_codes_page_is_reachable_after_login_completes(): void
    {
        $user = $this->loginToPending();
        $this->verifyEmailToSetup($user);
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
        \Illuminate\Support\Facades\Mail::fake();

        $secret = (new Google2FA())->generateSecretKey();
        $user = User::factory()->platform()->create([
            'email' => 'enrolled@test.local',
            'password' => Hash::make('password1234'),
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);

        $this->post('/login', ['identifier' => 'enrolled@test.local', 'password' => 'password1234']);

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

    public function test_email_otp_sent_on_login_and_verifies(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        [$user] = $this->loginEnrolledToPending();

        $capturedCode = null;
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\Auth\TwoFactorEmailCodeMail::class, function ($mail) use ($user, &$capturedCode) {
            $capturedCode = $mail->code;
            return $mail->hasTo($user->email);
        });
        $this->assertNotNull($capturedCode);

        $response = $this->post('/login/2fa/challenge', ['code' => $capturedCode]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('platform.dashboard'));
    }

    public function test_email_otp_resend_and_verify(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        [$user] = $this->loginEnrolledToPending();
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\Auth\TwoFactorEmailCodeMail::class);

        $this->post('/login/2fa/email')->assertRedirect();

        $capturedCode = \Illuminate\Support\Facades\Mail::sent(\App\Mail\Auth\TwoFactorEmailCodeMail::class)
            ->filter(fn ($m) => $m->hasTo($user->email))
            ->last()
            ->code;

        $response = $this->post('/login/2fa/challenge', ['code' => $capturedCode]);

        $response->assertRedirect(route('platform.dashboard'));
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
        $this->post('/login', ['identifier' => 'enrolled@test.local', 'password' => 'password1234']);
        $response2 = $this->post('/login/2fa/challenge', ['recovery_code' => $plainCodes[0]]);

        $this->assertGuest();
        $response2->assertSessionHasErrors('recovery_code');
    }

    public function test_recovery_code_row_lock_blocks_concurrent_transaction(): void
    {
        $config = config('database.connections.pgsql');
        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
        $pdo2 = new \PDO($dsn, $config['username'], $config['password'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo2->exec("SET lock_timeout = '200ms'");

        $password = Hash::make('password1234');
        $stmt = $pdo2->prepare(
            "INSERT INTO users (full_name, email, password, status, is_platform, created_at, updated_at) ".
            "VALUES (:name, :email, :password, 'active', true, now(), now()) RETURNING id"
        );
        $stmt->execute(['name' => 'Lock Test User', 'email' => 'lock-test@test.local', 'password' => $password]);
        $userId = (int) $stmt->fetchColumn();

        $secret = (new Google2FA())->generateSecretKey();
        $service = new \App\Services\Auth\TwoFactorService();
        $plainCodes = $service->generateRecoveryCodes();
        $user = User::find($userId);
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $service->hashRecoveryCodes($plainCodes),
        ])->save();

        $this->post('/login', ['identifier' => 'lock-test@test.local', 'password' => 'password1234']);

        $sawLockQuery = false;
        $blocked = false;
        \DB::listen(function ($query) use (&$sawLockQuery, &$blocked, $pdo2, $userId) {
            $sql = strtolower($query->sql);
            if ($sawLockQuery || ! str_contains($sql, 'for update') || ! str_contains($sql, 'users')) {
                return;
            }
            $sawLockQuery = true;

            try {
                $pdo2->beginTransaction();
                $pdo2->query("SELECT * FROM users WHERE id = {$userId} FOR UPDATE");
                $pdo2->commit();
            } catch (\PDOException $e) {
                $blocked = str_contains($e->getMessage(), 'lock timeout') || str_contains($e->getMessage(), '55P03');
                $pdo2->rollBack();
            }
        });

        $response = $this->post('/login/2fa/challenge', ['recovery_code' => $plainCodes[0]]);

        $this->assertTrue(
            $sawLockQuery,
            'Expected redeemRecoveryCode() to run a "... FOR UPDATE" query against users -- if this fails, lockForUpdate() is missing from the controller and the rest of this test proves nothing.'
        );
        $this->assertTrue(
            $blocked,
            "A second, fully independent connection should have been blocked by the row lock redeemRecoveryCode() takes. If this fails, the lock is not actually being taken/held, and the single-use guarantee is not real under concurrency."
        );
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('platform.dashboard'));
    }
}
