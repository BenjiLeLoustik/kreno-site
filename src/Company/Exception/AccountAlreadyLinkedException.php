<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class AccountAlreadyLinkedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Votre compte est déjà rattaché à une entreprise.')
        );
    }
}