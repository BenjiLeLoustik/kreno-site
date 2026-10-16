<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class AlreadyStaffException extends BusinessRuleException
{
    public function __construct(string $email)
    {
        parent::__construct(
            '{email} fait déjà partie de l\'équipe Kréno.',
            [
                'email' => $email
            ]
        );
    }
}