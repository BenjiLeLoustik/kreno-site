<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class PromoCodeRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Le code promo est obligatoire.');
    }
}