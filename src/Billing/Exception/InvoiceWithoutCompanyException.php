<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class InvoiceWithoutCompanyException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Facture sans entreprise.');
    }
}