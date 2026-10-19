<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class DuplicateSlotException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Ce créneau est déjà publié.')
        );
    }
}