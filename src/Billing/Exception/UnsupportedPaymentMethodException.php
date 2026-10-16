<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class UnsupportedPaymentMethodException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce moyen de paiement n\'est pas accepté.');
    }
}