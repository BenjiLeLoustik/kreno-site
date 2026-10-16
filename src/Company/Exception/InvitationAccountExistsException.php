<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class InvitationAccountExistsException extends BusinessRuleException
{
    public function __construct(?string $email)
    {
        parent::__construct(
            'Un compte existe déjà avec {email} : connectez-vous pour accepter l\'invitation.',
            [
                'email' => $email
            ],
        );
    }
}