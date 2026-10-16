<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;

class PhoneRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Indique un numéro de téléphone pour que l\'établissement puisse te joindre.');
    }
}