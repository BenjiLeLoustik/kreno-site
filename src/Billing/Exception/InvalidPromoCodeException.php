<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class InvalidPromoCodeException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce code n\'existe pas ou a expiré.');
    }
}