<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class PromoCodeAlreadyExistsException extends BusinessRuleException
{
    public function __construct(string $code, string $version)
    {
        parent::__construct(
            'Le code {code} existe déjà dans la {version}.',
            [
                'code' => $code,
                'version' => $version
            ],
        );
    }
}