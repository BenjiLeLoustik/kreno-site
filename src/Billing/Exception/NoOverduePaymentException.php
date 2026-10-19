<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class NoOverduePaymentException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Aucun paiement n\'est en retard.')
        );
    }
}