<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class CityLockedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Un établissement en ligne ne peut pas changer de ville.')
        );
    }
}