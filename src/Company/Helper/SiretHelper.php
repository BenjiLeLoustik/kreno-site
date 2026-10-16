<?php

namespace App\Company\Helper;

class SiretHelper
{
    public const SIRET_LENGTH = 14;

    public const SIREN_LENGTH = 9;

    public const LA_POSTE_PREFIX = '356000000';

    public static function normalize(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value) ?? '';
    }

    public static function isValidSiret(string $siret): bool
    {
        $siret = self::normalize($siret);

        if (strlen($siret) !== self::SIRET_LENGTH) {
            return false;
        }

        return self::luhn($siret) || str_starts_with($siret, self::LA_POSTE_PREFIX);
    }

    public static function isValidSiren(string $siren): bool
    {
        $siren = self::normalize($siren);

        return strlen($siren) === self::SIREN_LENGTH && self::luhn($siren);
    }

    public static function sirenOf(string $siret): string
    {
        return substr(self::normalize($siret), 0, self::SIREN_LENGTH);
    }

    public static function format(string $siret): string
    {
        $siret = self::normalize($siret);

        if (strlen($siret) !== self::SIRET_LENGTH) {
            return $siret;
        }

        return sprintf(
            '%s %s %s %s',
            substr($siret, 0, 3),
            substr($siret, 3, 3),
            substr($siret, 6, 3),
            substr($siret, 9),
        );
    }

    private static function luhn(string $number): bool
    {
        $sum = 0;
        $length = strlen($number);

        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $number[$length - 1 - $i];

            if ($i % 2 === 1) {
                $digit *= 2;
            }

            if ($digit > 9) {
                $digit -= 9;
            }

            $sum += $digit;
        }

        return $sum % 10 === 0;
    }
}