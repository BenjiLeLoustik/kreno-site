<?php

namespace App\Review\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class ReviewHiddenException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Cet avis a été masqué par notre équipe.')
        );
    }
}