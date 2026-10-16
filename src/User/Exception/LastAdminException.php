<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class LastAdminException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('L\'équipe doit garder au moins un administrateur.');
    }
}