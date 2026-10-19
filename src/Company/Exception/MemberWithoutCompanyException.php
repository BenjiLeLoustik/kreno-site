<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class MemberWithoutCompanyException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Aucune entreprise.')
        );
    }
}