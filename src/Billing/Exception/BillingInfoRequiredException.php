<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class BillingInfoRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Indiquez vos informations de facturation.');
    }
}