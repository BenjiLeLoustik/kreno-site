<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class VersionNotDeletableException extends BusinessRuleException
{
    public function __construct(string $code, string $status)
    {
        parent::__construct(
            'La {code} est {status} : elle ne peut pas être supprimée.',
            [
                'code' => $code,
                'status' => $status
            ],
        );
    }
}