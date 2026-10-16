<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class AccountNotSuspendableException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce compte ne peut pas être suspendu.');
    }
}