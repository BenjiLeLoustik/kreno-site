<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class ForeignPaymentMethodException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Ce moyen de paiement n\'appartient pas à l\'entreprise.')
        );
    }
}