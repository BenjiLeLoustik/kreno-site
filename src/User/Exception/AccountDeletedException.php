<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class AccountDeletedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Ce compte a été supprimé.')
        );
    }
}