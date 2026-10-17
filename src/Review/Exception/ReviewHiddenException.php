<?php

namespace App\Review\Exception;

use App\Shared\Exception\BusinessRuleException;

class ReviewHiddenException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cet avis a été masqué par notre équipe.');
    }
}