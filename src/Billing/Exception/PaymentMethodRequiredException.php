<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class PaymentMethodRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ajoutez un moyen de paiement pour changer de formule.');
    }
}