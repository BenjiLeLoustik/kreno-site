<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class InvoiceWithoutCompanyException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Facture sans entreprise.')
        );
    }
}