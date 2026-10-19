<?php

namespace App\Review\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class ReviewAlreadyHiddenException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Cet avis est déjà masqué.')
        );
    }
}