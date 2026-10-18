<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class InvitationAccountExistsException extends BusinessRuleException
{
    public function __construct(?string $email)
    {
        parent::__construct(
            Translation::translate('Un compte existe déjà avec {email} : connectez-vous pour accepter l\'invitation.'),
            [
                'email' => $email
            ],
        );
    }
}