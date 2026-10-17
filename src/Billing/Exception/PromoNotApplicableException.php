<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class PromoNotApplicableException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce code promo n\'est pas valable pour cette formule.');
    }
}