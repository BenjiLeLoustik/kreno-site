<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class MemberEstablishmentRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Choisissez l\'établissement de ce membre.')
        );
    }
}