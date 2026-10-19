<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class PromoCodeAlreadyExistsException extends BusinessRuleException
{
    public function __construct(string $code, string $version)
    {
        parent::__construct(
            Translation::translate('Le code {code} existe déjà dans la {version}.'),
            [
                'code' => $code,
                'version' => $version
            ],
        );
    }
}