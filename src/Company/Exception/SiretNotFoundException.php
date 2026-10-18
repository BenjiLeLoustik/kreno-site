<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class SiretNotFoundException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Nous n\'avons pas trouvé ce SIRET dans le registre public des entreprises.')
        );
    }
}