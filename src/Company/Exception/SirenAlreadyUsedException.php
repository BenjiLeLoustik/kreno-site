<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class SirenAlreadyUsedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Une autre entreprise utilise déjà ce SIREN.')
        );
    }
}