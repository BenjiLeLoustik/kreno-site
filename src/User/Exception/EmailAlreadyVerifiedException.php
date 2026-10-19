<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class EmailAlreadyVerifiedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Votre adresse e-mail est déjà vérifiée.')
        );
    }
}