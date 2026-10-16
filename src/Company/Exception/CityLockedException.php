<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class CityLockedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Un établissement en ligne ne peut pas changer de ville.');
    }
}