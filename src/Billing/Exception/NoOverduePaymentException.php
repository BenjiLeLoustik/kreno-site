<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class NoOverduePaymentException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Aucun paiement n\'est en retard.');
    }
}