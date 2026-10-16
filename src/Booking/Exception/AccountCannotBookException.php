<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;

class AccountCannotBookException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ton compte ne permet pas de réserver.');
    }
}