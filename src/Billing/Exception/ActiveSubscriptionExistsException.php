<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class ActiveSubscriptionExistsException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Votre entreprise a déjà une formule active : changez-la depuis la facturation.')
        );
    }
}