<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class SiretLockedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Le SIRET d\'un établissement en ligne ne peut pas être modifié. Contactez pro@kreno.fr.'),
        );
    }
}