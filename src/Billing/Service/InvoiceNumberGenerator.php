<?php

namespace App\Billing\Service;

use App\Billing\Enum\InvoiceType;
use App\Billing\Exception\InvalidInvoicePrefixException;
use App\Billing\Helper\InvoiceNumberHelper;
use NeoPHP\Component\Container\Attribute\Autowire;

class InvoiceNumberGenerator
{
    public const DEFAULT_PREFIXES = [
        'invoice' => 'KR-F',
        'credit_note' => 'KR-A',
    ];

    public function __construct(
        #[Autowire(config: 'packages.billing.numbering')]
        private array $prefixes = [],
    ) {
    }

    public function prefix(InvoiceType $type): string
    {
        $prefix = strtoupper(trim((string) ($this->prefixes[$type->value] ?? '')));

        if ($prefix === '') {
            $prefix = self::DEFAULT_PREFIXES[$type->value];
        }

        if (!InvoiceNumberHelper::isValidPrefix($prefix)) {
            throw new InvalidInvoicePrefixException($type, $prefix);
        }

        return $prefix;
    }

    public function format(InvoiceType $type, int $year, int $sequence): string
    {
        return sprintf('%s-%d-%05d', $this->prefix($type), $year, $sequence);
    }

    public function next(InvoiceType $type, int $year, int $count, callable $exists): string
    {
        $sequence = $count + 1;

        while ($exists($this->format($type, $year, $sequence))) {
            ++$sequence;
        }

        return $this->format($type, $year, $sequence);
    }
}