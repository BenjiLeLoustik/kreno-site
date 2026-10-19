<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class InvalidTokenException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Ce lien n\'est plus valide. Il a peut-être expiré ou déjà été utilisé : demandez-en un nouveau.')
        );
    }
}