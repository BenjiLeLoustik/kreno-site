<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class ActiveSubscriptionExistsException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Votre entreprise a déjà une formule active : changez-la depuis la facturation.');
    }
}