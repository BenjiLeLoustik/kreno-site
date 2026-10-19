<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class PaidInvoiceVoidException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Une facture payée ne peut pas être annulée : émettez un avoir.')
        );
    }
}