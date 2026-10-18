<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class OwnerRoleLockedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Le rôle du propriétaire ne peut pas être modifié.')
        );
    }
}