<?php

namespace App\Billing\Helper;

class InvoiceNumberHelper
{
    public const PREFIX_PATTERN = '[A-Z0-9]+(?:-[A-Z0-9]+)*';

    public const PATTERN = self::PREFIX_PATTERN . '-\d{4}-\d{5,}';

    public static function isValidPrefix(string $prefix): bool
    {
        return preg_match('/^' . self::PREFIX_PATTERN . '$/', $prefix) === 1;
    }
}