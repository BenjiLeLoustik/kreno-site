<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class InvalidCodeException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Ce code ne correspond pas ou a expiré. Vérifiez le dernier e-mail reçu, ou demandez-en un nouveau.'),
        );
    }
}