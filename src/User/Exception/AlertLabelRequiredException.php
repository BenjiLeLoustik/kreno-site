<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class AlertLabelRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Indiquez le quartier ou l\'adresse de l\'alerte.')
        );
    }
}