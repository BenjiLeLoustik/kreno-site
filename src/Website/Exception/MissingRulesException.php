<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class MissingRulesException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Toutes les règles de la plateforme doivent être renseignées.')
        );
    }
}