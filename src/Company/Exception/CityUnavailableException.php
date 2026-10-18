<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class CityUnavailableException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Kréno n\'est pas encore disponible dans cette ville.')
        );
    }
}