<?php

namespace App\Billing\Exception;

use App\Billing\Enum\InvoiceType;
use App\Shared\Helper\Translation;
use LogicException;

class InvalidInvoicePrefixException extends LogicException
{
    public function __construct(InvoiceType $type, string $prefix)
    {
        parent::__construct(sprintf(
            Translation::translate('Préfixe de facturation invalide pour "%s" : "%s" (lettres majuscules, chiffres et tirets uniquement).'),
            $type->value,
            $prefix,
        ));
    }
}