<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class AccountCannotBookException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Ton compte ne permet pas de réserver.')
        );
    }
}