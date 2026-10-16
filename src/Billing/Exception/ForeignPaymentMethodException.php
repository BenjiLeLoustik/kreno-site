<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class ForeignPaymentMethodException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce moyen de paiement n\'appartient pas à l\'entreprise.');
    }
}