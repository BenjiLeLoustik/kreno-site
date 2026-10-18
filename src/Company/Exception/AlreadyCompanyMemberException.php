<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class AlreadyCompanyMemberException extends BusinessRuleException
{
    public function __construct(string $company)
    {
        parent::__construct(
            Translation::translate('Vous faites déjà partie de {company}.'),
            [
                'company' => $company
            ],
        );
    }
}