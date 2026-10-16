<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class NoCreditableInvoiceException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Aucune facture sur laquelle émettre un avoir.');
    }
}