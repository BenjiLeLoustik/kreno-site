<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class InvalidSiretException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Ce numéro SIRET n\'est pas valide. Il contient 14 chiffres, il figure sur votre Kbis ou votre avis de situation INSEE.'),
        );
    }
}