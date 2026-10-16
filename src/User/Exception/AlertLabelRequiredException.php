<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class AlertLabelRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Indiquez le quartier ou l\'adresse de l\'alerte.');
    }
}