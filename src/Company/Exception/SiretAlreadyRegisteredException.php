<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class SiretAlreadyRegisteredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Cet établissement est déjà inscrit sur Kréno. Contactez pro@kreno.fr si vous pensez qu\'il s\'agit d\'une erreur.'),
        );
    }
}