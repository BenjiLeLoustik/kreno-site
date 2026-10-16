<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class UnknownCategoryException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Catégorie inconnue.');
    }
}