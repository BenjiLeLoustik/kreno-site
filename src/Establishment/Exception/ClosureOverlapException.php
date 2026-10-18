<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class ClosureOverlapException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Une fermeture existe déjà sur cette période.')
        );
    }
}