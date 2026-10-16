<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class InvalidStaffRoleException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce rôle n\'existe pas pour l\'équipe Kréno.');
    }
}