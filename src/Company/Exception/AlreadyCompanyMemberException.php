<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class AlreadyCompanyMemberException extends BusinessRuleException
{
    public function __construct(string $company)
    {
        parent::__construct(
            'Vous faites déjà partie de {company}.',
            [
                'company' => $company
            ],
        );
    }
}