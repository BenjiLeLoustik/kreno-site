<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class PastClosureException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Cette fermeture est déjà passée.')
        );
    }
}