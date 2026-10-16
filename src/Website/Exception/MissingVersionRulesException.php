<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class MissingVersionRulesException extends BusinessRuleException
{
    public function __construct(string $code)
    {
        parent::__construct(
            'La {code} n\'a pas de règles : elle ne peut pas être mise en ligne.',
            [
                'code' => $code
            ],
        );
    }
}