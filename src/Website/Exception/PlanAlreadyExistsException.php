<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class PlanAlreadyExistsException extends BusinessRuleException
{
    public function __construct(string $code, string $version)
    {
        parent::__construct(
            'La formule « {code} » existe déjà dans la {version}.',
            [
                'code' => $code,
                'version' => $version
            ],
        );
    }
}