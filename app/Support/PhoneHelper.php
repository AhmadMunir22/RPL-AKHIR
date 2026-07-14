<?php

namespace App\Support;

class PhoneHelper
{
    /**
     * Normalize to 62xxxxxxxx (storage & display consistency).
     */
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62' . $digits;
        }

        return '62' . $digits;
    }

    /**
     * Format for Kata AI API (628xxxxxxxx — format yang terbukti terkirim).
     */
    public static function kataAiTarget(string $phone): string
    {
        return self::normalize($phone);
    }

    public static function digitsMatch(string $a, string $b): bool
    {
        return self::normalize($a) === self::normalize($b);
    }

    /**
     * Display as 08xxxxxxxx for users.
     */
    public static function display(string $phone): string
    {
        $normalized = self::normalize($phone);

        if (str_starts_with($normalized, '62') && strlen($normalized) > 2) {
            return '0' . substr($normalized, 2);
        }

        return $phone;
    }
}
