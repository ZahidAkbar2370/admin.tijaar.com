<?php

namespace App\Support;

class PhoneHelper
{
    /**
     * Normalize Pakistani mobile to stored form: 03XXXXXXXXX (11 digits).
     * Accepts: 03…, 923…, +923…, 3XXXXXXXXX, 0092….
     */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        // 923XXXXXXXXX → 03XXXXXXXXX
        if (str_starts_with($digits, '92') && strlen($digits) >= 12 && ($digits[2] ?? '') === '3') {
            $digits = '0'.substr($digits, 2, 10);
        }

        // 3XXXXXXXXX (10 digits) → 03XXXXXXXXX
        if (str_starts_with($digits, '3') && strlen($digits) === 10) {
            $digits = '0'.$digits;
        }

        // Already 03… but too long — trim
        if (str_starts_with($digits, '03') && strlen($digits) > 11) {
            $digits = substr($digits, 0, 11);
        }

        if (! self::isValid($digits)) {
            return null;
        }

        return $digits;
    }

    /**
     * Valid storage format: 03 + 9 digits.
     */
    public static function isValid(?string $phone): bool
    {
        if ($phone === null || $phone === '') {
            return false;
        }

        return (bool) preg_match('/^03\d{9}$/', $phone);
    }

    /**
     * JazzCash / local gateway format (same as storage).
     */
    public static function toJazzCashFormat(?string $phone): ?string
    {
        return self::normalize($phone);
    }

    /**
     * International digits without +: 923XXXXXXXXX (for WhatsApp / Waghl APIs).
     */
    public static function toInternational(?string $phone): ?string
    {
        $normalized = self::normalize($phone);
        if ($normalized === null) {
            return null;
        }

        return '92'.substr($normalized, 1);
    }
}
