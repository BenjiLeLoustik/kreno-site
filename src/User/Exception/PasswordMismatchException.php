<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class PasswordMismatchException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Les deux mots de passe ne sont pas identiques.')
        );
    }
}