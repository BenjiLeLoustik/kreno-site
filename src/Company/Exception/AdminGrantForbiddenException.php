<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class AdminGrantForbiddenException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Seuls le propriétaire et les administrateurs peuvent nommer un administrateur.')
        );
    }
}