<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class InvitationDetailsRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Indiquez votre prénom et choisissez un mot de passe.');
    }
}