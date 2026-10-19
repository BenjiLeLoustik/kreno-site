<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class EstablishmentUnavailableException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Cet établissement n\'est pas disponible.')
        );
    }
}