<?php

namespace App\Catalog\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class CityNotEmptyException extends BusinessRuleException
{
    public function __construct(string $city)
    {
        parent::__construct(
            Translation::translate('{city} a des établissements : fermez-la plutôt que de la supprimer.'),
            [
                'city' => $city
            ],
        );
    }
}