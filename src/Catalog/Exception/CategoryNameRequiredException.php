<?php

namespace App\Catalog\Exception;

use App\Shared\Exception\BusinessRuleException;

class CategoryNameRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Le nom de la catégorie est obligatoire.');
    }
}