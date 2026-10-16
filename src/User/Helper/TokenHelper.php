<?php

namespace App\User\Helper;

class TokenHelper
{
    public static function generate(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    public static function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public static function code(int $length = 6): string
    {
        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }
}