<?php

namespace App\Catalog\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class CityNameRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Le nom de la ville est obligatoire.')
        );
    }
}