<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class AlertTimeRangeException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('L\'heure de fin doit être après l\'heure de début.')
        );
    }
}