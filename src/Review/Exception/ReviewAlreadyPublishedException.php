<?php

namespace App\Review\Exception;

use App\Shared\Exception\BusinessRuleException;

class ReviewAlreadyPublishedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cet avis est déjà en ligne.');
    }
}