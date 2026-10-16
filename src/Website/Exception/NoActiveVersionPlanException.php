<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class NoActiveVersionPlanException extends BusinessRuleException
{
    public function __construct(string $code)
    {
        parent::__construct(
            'La {code} n\'a aucune formule active : elle ne peut pas être mise en ligne.',
            [
                'code' => $code
            ],
        );
    }
}