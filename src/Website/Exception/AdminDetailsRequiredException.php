<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class AdminDetailsRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            'Indique le prénom et une adresse e-mail valide pour le compte administrateur.',
        );
    }
}