<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class NotStaffMemberException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cette personne ne fait pas partie de l\'équipe Kréno.');
    }
}