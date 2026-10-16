<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class VersionAlreadyScheduledException extends BusinessRuleException
{
    public function __construct(string $code)
    {
        parent::__construct(
            'La {code} est déjà planifiée. Annulez sa planification avant d\'en planifier une autre.',
            [
                'code' => $code
            ],
        );
    }
}