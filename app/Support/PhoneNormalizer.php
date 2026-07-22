<?php

namespace App\Support;

/** Normalize Uganda and international phone numbers to E.164 where possible. */
class PhoneNormalizer
{
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        $digits = preg_replace('/[^\d+]/', '', $trimmed) ?? '';
        if ($digits === '') {
            return null;
        }

        // Local 0XXXXXXXXX → +256XXXXXXXXX
        if (preg_match('/^0(\d{9})$/', $digits, $m)) {
            return '+256'.$m[1];
        }

        // 256XXXXXXXXX without plus
        if (preg_match('/^256(\d{9})$/', $digits, $m)) {
            return '+256'.$m[1];
        }

        // Already E.164-ish
        if (str_starts_with($digits, '+')) {
            return $digits;
        }

        if (preg_match('/^\d{9,15}$/', $digits)) {
            return '+'.$digits;
        }

        return $digits;
    }
}
