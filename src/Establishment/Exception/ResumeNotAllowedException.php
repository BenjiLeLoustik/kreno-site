<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class ResumeNotAllowedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Seul un établissement suspendu peut être remis en ligne.')
        );
    }
}