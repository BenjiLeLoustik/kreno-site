<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class MissingEstablishmentFieldException extends BusinessRuleException
{
    public function __construct(string $field)
    {
        parent::__construct(
            Translation::translate('Il manque {field} de l\'établissement.'),
            [
                'field' => $field
            ],
        );
    }
}