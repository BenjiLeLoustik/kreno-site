<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class AccountDeletedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce compte a été supprimé.');
    }
}