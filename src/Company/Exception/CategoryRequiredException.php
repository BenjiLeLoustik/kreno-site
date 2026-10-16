<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class CategoryRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Choisissez la catégorie de votre établissement.');
    }
}