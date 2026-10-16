<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class InvoiceNotCreditableException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cette facture ne peut pas faire l\'objet d\'un avoir.');
    }
}