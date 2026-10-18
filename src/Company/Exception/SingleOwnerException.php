<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class SingleOwnerException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Il ne peut y avoir qu\'un seul propriétaire.')
        );
    }
}