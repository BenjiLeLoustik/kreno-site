<?php

namespace App\Booking\Helper;

class ReferenceHelper
{
    public const PREFIX = 'KR-';

    public const PATTERN = 'KR-\d{6}';

    public static function generate(callable $exists): string
    {
        do {
            $reference = self::PREFIX . random_int(100000, 999999);
        } while ($exists($reference));

        return $reference;
    }
}