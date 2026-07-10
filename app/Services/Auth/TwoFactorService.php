<?php

namespace App\Services\Auth;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function qrCodeSvg(string $email, string $secret): string
    {
        $issuer = config('app.name');
        $otpauthUrl = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s',
            rawurlencode($issuer),
            rawurlencode($email),
            $secret,
            rawurlencode($issuer),
        );

        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());

        return (new Writer($renderer))->writeString($otpauthUrl);
    }

    public function verifyTotp(string $secret, string $code): bool
    {
        return (bool) $this->google2fa->verifyKey($secret, $code);
    }

    public function generateRecoveryCodes(): array
    {
        return array_map(
            fn () => Str::lower(Str::random(4)).'-'.Str::lower(Str::random(4)),
            range(1, 10),
        );
    }

    public function hashRecoveryCodes(array $plaintextCodes): array
    {
        return array_map(fn (string $code) => Hash::make($code), $plaintextCodes);
    }

    public function generateEmailOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
