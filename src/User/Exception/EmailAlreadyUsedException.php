<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class EmailAlreadyUsedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Cette adresse e-mail est déjà utilisée par un autre compte.')
        );
    }
}