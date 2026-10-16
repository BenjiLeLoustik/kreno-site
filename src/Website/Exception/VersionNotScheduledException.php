<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class VersionNotScheduledException extends BusinessRuleException
{
    public function __construct(string $code)
    {
        parent::__construct('La {code} n\'est pas planifiée.', ['code' => $code]);
    }
}