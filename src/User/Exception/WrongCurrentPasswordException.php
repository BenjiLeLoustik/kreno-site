<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class WrongCurrentPasswordException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Le mot de passe actuel est incorrect.')
        );
    }
}