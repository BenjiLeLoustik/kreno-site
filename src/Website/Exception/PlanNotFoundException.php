<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class PlanNotFoundException extends BusinessRuleException
{
    public function __construct(string $code, string $version)
    {
        parent::__construct(
            'La formule « {code} » n\'existe pas dans la {version}.',
            [
                'code' => $code,
                'version' => $version
            ],
        );
    }
}