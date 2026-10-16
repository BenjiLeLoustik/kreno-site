<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class AlreadyInstalledException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Le site est déjà installé : une version est en ligne.');
    }
}