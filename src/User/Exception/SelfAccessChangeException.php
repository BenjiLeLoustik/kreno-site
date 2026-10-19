<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class SelfAccessChangeException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Vous ne pouvez pas modifier votre propre accès.')
        );
    }
}