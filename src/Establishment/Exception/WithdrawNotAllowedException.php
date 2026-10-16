<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class WithdrawNotAllowedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Seul un créneau en ligne peut être retiré.');
    }
}