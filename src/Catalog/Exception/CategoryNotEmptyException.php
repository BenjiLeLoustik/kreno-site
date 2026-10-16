<?php

namespace App\Catalog\Exception;

use App\Shared\Exception\BusinessRuleException;

class CategoryNotEmptyException extends BusinessRuleException
{
    public function __construct(string $category)
    {
        parent::__construct(
            '{category} contient des établissements : masquez-la plutôt que de la supprimer.',
            [
                'category' => $category
            ],
        );
    }
}