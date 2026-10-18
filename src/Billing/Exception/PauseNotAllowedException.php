<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class PauseNotAllowedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Seul un abonnement actif peut être mis en pause.')
        );
    }
}