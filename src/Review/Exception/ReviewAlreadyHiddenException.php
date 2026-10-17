<?php

namespace App\Review\Exception;

use App\Shared\Exception\BusinessRuleException;

class ReviewAlreadyHiddenException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cet avis est déjà masqué.');
    }
}