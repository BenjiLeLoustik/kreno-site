<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class PaymentSetupRejectedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Votre banque n\'a pas validé l\'enregistrement de ce moyen de paiement.')
        );
    }
}