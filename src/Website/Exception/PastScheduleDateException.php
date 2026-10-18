<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class PastScheduleDateException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('La date de mise en ligne doit être dans le futur.')
        );
    }
}