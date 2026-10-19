<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class CityNotOpenException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Cette ville n\'est pas encore ouverte sur Kréno.')
        );
    }
}