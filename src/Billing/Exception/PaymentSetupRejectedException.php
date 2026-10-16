<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class PaymentSetupRejectedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Votre banque n\'a pas validé l\'enregistrement de ce moyen de paiement.');
    }
}