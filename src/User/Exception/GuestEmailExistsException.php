<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class GuestEmailExistsException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Un compte existe déjà avec cette adresse e-mail : connecte-toi pour réserver.')
        );
    }
}