<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class SiretAlreadyRegisteredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            'Cet établissement est déjà inscrit sur Kréno. Contactez pro@kreno.fr si vous pensez qu\'il s\'agit d\'une erreur.',
        );
    }
}