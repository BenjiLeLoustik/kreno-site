<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class InvalidSlotDateException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Le jour ou l\'heure du créneau est invalide.')
        );
    }
}