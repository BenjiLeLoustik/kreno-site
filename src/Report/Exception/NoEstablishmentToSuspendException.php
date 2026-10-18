<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class NoEstablishmentToSuspendException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Aucun établissement à suspendre.')
        );
    }
}