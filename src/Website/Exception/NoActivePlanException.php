<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class NoActivePlanException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Activez au moins une formule pour les professionnels.')
        );
    }
}