<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class InvoiceAlreadySettledException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cette facture est déjà réglée.');
    }
}