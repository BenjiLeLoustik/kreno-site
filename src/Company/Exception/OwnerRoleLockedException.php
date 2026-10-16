<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class OwnerRoleLockedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Le rôle du propriétaire ne peut pas être modifié.');
    }
}