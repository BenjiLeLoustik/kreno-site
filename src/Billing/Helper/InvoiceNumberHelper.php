<?php

namespace App\Billing\Helper;

use App\Billing\Enum\InvoiceType;

class InvoiceNumberHelper
{
    public const PREFIXES = [
        'invoice' => 'KR-F',
        'credit_note' => 'KR-A',
    ];

    public const PATTERN = 'KR-[FA]-\d{4}-\d{5,}';

    public static function format(InvoiceType $type, int $year, int $sequence): string
    {
        return sprintf('%s-%d-%05d', self::PREFIXES[$type->value], $year, $sequence);
    }

    public static function next(InvoiceType $type, int $year, int $count, callable $exists): string
    {
        $sequence = $count + 1;

        while ($exists(self::format($type, $year, $sequence))) {
            ++$sequence;
        }

        return self::format($type, $year, $sequence);
    }
}