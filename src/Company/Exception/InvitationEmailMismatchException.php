<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class InvitationEmailMismatchException extends BusinessRuleException
{
    public function __construct(string $email)
    {
        parent::__construct(
            'Cette invitation a été envoyée à {email}. Connectez-vous avec cette adresse pour l\'accepter.',
            [
                'email' => $email
            ],
        );
    }
}