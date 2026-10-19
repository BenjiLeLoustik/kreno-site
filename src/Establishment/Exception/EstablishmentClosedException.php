<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class EstablishmentClosedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('L\'établissement est fermé ce jour-là.')
        );
    }
}