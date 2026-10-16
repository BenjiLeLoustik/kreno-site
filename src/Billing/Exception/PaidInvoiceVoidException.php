<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class PaidInvoiceVoidException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Une facture payée ne peut pas être annulée : émettez un avoir.');
    }
}