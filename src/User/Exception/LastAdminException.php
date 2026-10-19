<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class LastAdminException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('L\'équipe doit garder au moins un administrateur.')
        );
    }
}