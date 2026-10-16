<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class PlanIdentityRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Le code et le nom de la formule sont obligatoires.');
    }
}