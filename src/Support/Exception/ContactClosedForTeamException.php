<?php

namespace App\Support\Exception;

use App\Shared\Exception\BusinessRuleException;

class ContactClosedForTeamException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cette demande est fermée : rouvrez-la pour répondre.');
    }
}