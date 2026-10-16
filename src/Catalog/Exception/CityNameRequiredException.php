<?php

namespace App\Catalog\Exception;

use App\Shared\Exception\BusinessRuleException;

class CityNameRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Le nom de la ville est obligatoire.');
    }
}