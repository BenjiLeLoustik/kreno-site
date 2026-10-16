<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class AlreadyTeamMemberException extends BusinessRuleException
{
    public function __construct(string $email)
    {
        parent::__construct(
            '{email} fait déjà partie de l\'équipe.',
            [
                'email' => $email
            ],
        );
    }
}