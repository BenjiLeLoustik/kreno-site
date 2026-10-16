<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class SirenAlreadyUsedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Une autre entreprise utilise déjà ce SIREN.');
    }
}