<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class DefaultDiscountOutOfRangeException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('La remise par défaut doit être comprise entre la minimale et la maximale.');
    }
}