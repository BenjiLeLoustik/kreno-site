<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class EstablishmentTeamForbiddenException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Vous ne pouvez pas gérer l\'équipe de cet établissement.');
    }
}