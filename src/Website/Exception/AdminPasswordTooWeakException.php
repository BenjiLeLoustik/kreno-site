<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class AdminPasswordTooWeakException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Le mot de passe administrateur doit faire au moins 12 caractères, avec des lettres et des chiffres.'),
        );
    }
}