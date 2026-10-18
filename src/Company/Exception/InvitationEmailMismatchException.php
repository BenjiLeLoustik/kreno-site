<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class InvitationEmailMismatchException extends BusinessRuleException
{
    public function __construct(string $email)
    {
        parent::__construct(
            Translation::translate('Cette invitation a été envoyée à {email}. Connectez-vous avec cette adresse pour l\'accepter.'),
            [
                'email' => $email
            ],
        );
    }
}