<?php

namespace App\Catalog\Exception;

use App\Shared\Exception\BusinessRuleException;

class CityNotEmptyException extends BusinessRuleException
{
    public function __construct(string $city)
    {
        parent::__construct(
            '{city} a des établissements : fermez-la plutôt que de la supprimer.',
            [
                'city' => $city
            ],
        );
    }
}