<?php

namespace App\Shared\Helper;

class JsonStatusHelper
{
    public const OK = 200;

    public const UNPROCESSABLE = 422;

    public static function of(array $body): int
    {
        if (array_key_exists('error', $body)) {
            return self::UNPROCESSABLE;
        }

        return self::OK;
    }
}