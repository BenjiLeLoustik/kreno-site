<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class InvalidTokenException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce lien n\'est plus valide. Il a peut-être expiré ou déjà été utilisé : demandez-en un nouveau.');
    }
}