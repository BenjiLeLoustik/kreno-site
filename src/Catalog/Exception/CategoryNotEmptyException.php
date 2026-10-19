<?php

namespace App\Catalog\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class CategoryNotEmptyException extends BusinessRuleException
{
    public function __construct(string $category)
    {
        parent::__construct(
            Translation::translate('{category} contient des établissements : masquez-la plutôt que de la supprimer.'),
            [
                'category' => $category
            ],
        );
    }
}