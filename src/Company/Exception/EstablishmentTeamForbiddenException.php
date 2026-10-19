<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class EstablishmentTeamForbiddenException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Vous ne pouvez pas gérer l\'équipe de cet établissement.')
        );
    }
}