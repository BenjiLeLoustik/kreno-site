<?php

namespace App\Company\Helper;

class SiretHelper
{
    public static function normalize(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value) ?? '';
    }

    public static function isValidSiret(string $siret): bool
    {
        $siret = self::normalize($siret);

        return strlen($siret) === 14 && (self::luhn($siret) || str_starts_with($siret, '356000000'));
    }

    public static function isValidSiren(string $siren): bool
    {
        $siren = self::normalize($siren);

        return strlen($siren) === 9 && self::luhn($siren);
    }

    public static function sirenOf(string $siret): string
    {
        return substr(self::normalize($siret), 0, 9);
    }

    public static function format(string $siret): string
    {
        $siret = self::normalize($siret);

        return strlen($siret) === 14 ? sprintf('%s %s %s %s', substr($siret, 0, 3), substr($siret, 3, 3), substr($siret, 6, 3), substr($siret, 9)) : $siret;
    }

    private static function luhn(string $number): bool
    {
        $sum = 0;
        $length = strlen($number);

        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $number[$length - 1 - $i];

            if ($i % 2 === 1) {
                $digit *= 2;
                $digit = $digit > 9 ? $digit - 9 : $digit;
            }

            $sum += $digit;
        }

        return $sum % 10 === 0;
    }
}