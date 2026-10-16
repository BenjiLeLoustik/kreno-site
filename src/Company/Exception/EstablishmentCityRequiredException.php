<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class EstablishmentCityRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Choisissez la ville de l\'établissement.');
    }
}