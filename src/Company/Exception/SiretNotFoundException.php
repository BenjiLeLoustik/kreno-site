<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class SiretNotFoundException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Nous n\'avons pas trouvé ce SIRET dans le registre public des entreprises.');
    }
}