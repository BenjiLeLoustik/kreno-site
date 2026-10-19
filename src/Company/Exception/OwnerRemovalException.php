<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class OwnerRemovalException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Le propriétaire ne peut pas être retiré de l\'entreprise.')
        );
    }
}