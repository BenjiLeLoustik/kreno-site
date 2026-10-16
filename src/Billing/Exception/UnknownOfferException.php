<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class UnknownOfferException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Offre inconnue.');
    }
}