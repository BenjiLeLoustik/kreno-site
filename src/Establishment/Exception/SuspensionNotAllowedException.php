<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class SuspensionNotAllowedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Seul un établissement en ligne peut être suspendu.')
        );
    }
}