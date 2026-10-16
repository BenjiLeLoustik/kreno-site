<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class InactiveAccountException extends BusinessRuleException
{
    public function __construct(string $email)
    {
        parent::__construct(
            'Le compte {email} n\'est pas actif : il ne peut pas rejoindre l\'équipe.',
            [
                'email' => $email
            ],
        );
    }
}