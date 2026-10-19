<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class PaymentMethodRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Ajoutez un moyen de paiement pour changer de formule.')
        );
    }
}