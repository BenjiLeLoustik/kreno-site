<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class EstablishmentNotPendingException extends BusinessRuleException
{
    public function __construct(?string $name)
    {
        parent::__construct(
            Translation::translate('{name} n\'est plus en attente de vérification.'),
            [
                'name' => $name
            ]
        );
    }
}