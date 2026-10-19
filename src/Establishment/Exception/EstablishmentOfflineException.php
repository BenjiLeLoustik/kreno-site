<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class EstablishmentOfflineException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('L\'établissement doit être en ligne pour publier un créneau.')
        );
    }
}