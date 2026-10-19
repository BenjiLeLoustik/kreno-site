<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class LastPaymentMethodException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Ajoutez un autre moyen de paiement avant de supprimer celui-ci.')
        );
    }
}