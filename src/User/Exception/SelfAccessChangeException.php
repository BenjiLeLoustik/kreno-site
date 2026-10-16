<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class SelfAccessChangeException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Vous ne pouvez pas modifier votre propre accès.');
    }
}