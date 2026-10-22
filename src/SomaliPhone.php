<?php

declare(strict_types=1);

namespace NovoConnect\SomaliPhone;

final class SomaliPhone
{
    /** Arabic-Indic and Eastern Arabic-Indic digits. */
    private const DIGITS = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];

    /**
     * Parses a Somali mobile number written in any common format.
     *
     * Accepts "0634123456", "634123456", "+252 63 412 3456", "00252634123456",
     * "252-63-412-3456", "(063) 412 3456", Arabic-Indic digits and the
     * common "+252 063..." mistake.
     *
     * @throws InvalidPhoneNumber
     */
    public static function parse(?string $input): PhoneNumber
    {
        return self::tryParse($input) ?? throw InvalidPhoneNumber::for($input);
    }

    public static function tryParse(?string $input): ?PhoneNumber
    {
        $national = self::extractNational((string) $input);

        if ($national === null || ! self::isValidNational($national)) {
            return null;
        }

        return PhoneNumber::fromNational($national);
    }

    /**
     * Returns the number as "252XXXXXXXXX", or null if it isn't valid.
     */
    public static function normalize(?string $input): ?string
    {
        return self::tryParse($input)?->msisdn();
    }

    public static function isValid(?string $input): bool
    {
        return self::tryParse($input) !== null;
    }

    /**
     * Whether a 9-digit national number is on a prefix in use.
     *
     * @internal
     */
    public static function isValidNational(string $national): bool
    {
        return preg_match('/^\d{9}$/', $national) === 1
            && Prefixes::has(substr($national, 0, 2));
    }

    private static function extractNational(string $input): ?string
    {
        $input = strtr(trim($input), self::DIGITS);

        // Digits and the usual separators, with an optional leading "+".
        if (preg_match('/^\+?[\d\s\-().]+$/u', $input) !== 1) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $input) ?? '';

        // After "+" or "00" we need 252, or "+63..." would pass as Telesom.
        $international = str_starts_with($input, '+') || str_starts_with($digits, '00');

        foreach (['00252', '252'] as $countryCode) {
            if (str_starts_with($digits, $countryCode)) {
                $digits = substr($digits, strlen($countryCode));
                $international = false;
                break;
            }
        }

        if ($international) {
            return null;
        }

        // Trunk prefix. Also covers "+252 0634...".
        if (strlen($digits) === 10 && $digits[0] === '0') {
            $digits = substr($digits, 1);
        }

        return strlen($digits) === 9 ? $digits : null;
    }
}
