<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class NoActivePlanException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Activez au moins une formule pour les professionnels.');
    }
}