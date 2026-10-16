<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class EstablishmentNotPendingException extends BusinessRuleException
{
    public function __construct(string $name)
    {
        parent::__construct(
            '{name} n\'est pas en attente de vérification.',
            [
                'name' => $name
            ],
        );
    }
}