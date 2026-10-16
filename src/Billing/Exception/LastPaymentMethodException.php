<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class LastPaymentMethodException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ajoutez un autre moyen de paiement avant de supprimer celui-ci.');
    }
}