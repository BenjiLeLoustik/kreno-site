<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class NoUnpaidInvoiceException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Aucune facture à régler.');
    }
}