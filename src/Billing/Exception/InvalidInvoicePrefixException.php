<?php

namespace App\Billing\Exception;

use App\Billing\Enum\InvoiceType;
use LogicException;

class InvalidInvoicePrefixException extends LogicException
{
    public function __construct(InvoiceType $type, string $prefix)
    {
        parent::__construct(sprintf(
            'Préfixe de facturation invalide pour "%s" : "%s" (lettres majuscules, chiffres et tirets uniquement).',
            $type->value,
            $prefix,
        ));
    }
}