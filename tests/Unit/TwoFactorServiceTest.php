<?php

namespace Tests\Unit;

use App\Services\Auth\TwoFactorService;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorServiceTest extends TestCase
{
    public function test_generate_secret_is_valid_base32(): void
    {
        $service = new TwoFactorService();
        $secret = $service->generateSecret();

        $this->assertMatchesRegularExpression('/^[A-Z2-7]{16,}$/', $secret);
    }

    public function test_verify_totp_accepts_current_code(): void
    {
        $service = new TwoFactorService();
        $secret = $service->generateSecret();
        $currentCode = (new Google2FA())->getCurrentOtp($secret);

        $this->assertTrue($service->verifyTotp($secret, $currentCode));
    }

    public function test_verify_totp_rejects_wrong_code(): void
    {
        $service = new TwoFactorService();
        $secret = $service->generateSecret();

        $this->assertFalse($service->verifyTotp($secret, '000000'));
    }

    public function test_qr_code_svg_contains_svg_markup(): void
    {
        $service = new TwoFactorService();
        $svg = $service->qrCodeSvg('admin@voxsign.co.ug', $service->generateSecret());

        $this->assertStringContainsString('<svg', $svg);
    }

    public function test_generate_recovery_codes_returns_ten_unique_codes(): void
    {
        $service = new TwoFactorService();
        $codes = $service->generateRecoveryCodes();

        $this->assertCount(10, $codes);
        $this->assertCount(10, array_unique($codes));
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[a-z0-9]{4}-[a-z0-9]{4}$/', $code);
        }
    }

    public function test_hash_recovery_codes_produces_verifiable_hashes(): void
    {
        $service = new TwoFactorService();
        $plain = $service->generateRecoveryCodes();
        $hashed = $service->hashRecoveryCodes($plain);

        $this->assertCount(10, $hashed);
        $this->assertTrue(Hash::check($plain[0], $hashed[0]));
    }

    public function test_generate_email_otp_is_six_digits(): void
    {
        $service = new TwoFactorService();
        $otp = $service->generateEmailOtp();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp);
    }
}
