<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class SamePasswordException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Le nouveau mot de passe doit être différent de l\'actuel.')
        );
    }
}