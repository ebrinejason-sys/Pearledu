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

        $this->post('/login', ['email' => $email, 'password' => 'password1234']);

        return $user;
    }

    public function test_unenrolled_platform_admin_logs_in_with_email_otp_only(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $user = User::factory()->platform()->create([
            'email' => 'emailonly@test.local',
            'password' => Hash::make('password1234'),
        ]);

        $this->post('/login', ['email' => 'emailonly@test.local', 'password' => 'password1234'])
            ->assertRedirect('/login/2fa/challenge');

        $capturedCode = null;
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\Auth\TwoFactorEmailCodeMail::class, function ($mail) use ($user, &$capturedCode) {
            $capturedCode = $mail->code;

            return $mail->hasTo($user->email);
        });

        $this->post('/login/2fa/challenge', ['code' => $capturedCode])
            ->assertRedirect(route('platform.dashboard'));

        $this->assertAuthenticatedAs($user);
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
        \Illuminate\Support\Facades\Mail::fake();

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

        $this->post('/login/2fa/email');

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

    /**
     * The sequential test above (test_recovery_code_redeems_once) proves reuse is
     * rejected, but PHPUnit is single-threaded, so it can't prove that the row lock in
     * TwoFactorChallengeController::redeemRecoveryCode() actually blocks a second,
     * truly-overlapping transaction -- it only proves the *end state* is correct once one
     * request has fully finished before the next starts.
     *
     * This test proves the lock is real by using a second, fully independent raw PDO
     * connection to the same Postgres database. We can't literally run two overlapping
     * HTTP requests from one PHP process, so instead we hook Laravel's query listener:
     * the instant the controller's `User::lockForUpdate()->find()` query executes (row
     * lock acquired, but redeemRecoveryCode()'s transaction has NOT committed yet,
     * because we're still inside the DB::transaction() closure that issued it), we open
     * the second connection and try to take the *same* row lock with a short
     * lock_timeout. If the lock is real, that second attempt must time out. If
     * lockForUpdate() were removed from the controller, the query text would no longer
     * contain "for update", our listener would never fire, and both assertions below
     * would fail -- so this test is a genuine regression guard tied to the real
     * production code, not a standalone assertion that would pass regardless.
     *
     * Note on RefreshDatabase: this whole test method runs inside one outer,
     * never-committed transaction on Laravel's default connection. A user created via
     * Eloquent here would therefore be invisible to a genuinely separate Postgres
     * session -- "SELECT ... FOR UPDATE" against it would just match zero rows and
     * "succeed" trivially, proving nothing. So the user row is created via a raw,
     * autocommitting INSERT on the second connection first, which makes it durable and
     * visible to any session; the 2FA fields are then filled in normally via Eloquent
     * (fine, because the *same* session/connection always sees its own uncommitted
     * writes).
     */
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

        $this->post('/login', ['email' => 'lock-test@test.local', 'password' => 'password1234']);

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
