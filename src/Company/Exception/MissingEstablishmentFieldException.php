<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class MissingEstablishmentFieldException extends BusinessRuleException
{
    public function __construct(string $field)
    {
        parent::__construct(
            'Il manque {field} de l\'établissement.',
            [
                'field' => $field
            ],
        );
    }
}