<?php

namespace App\Catalog\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class CityUnavailableException extends NotFoundHttpException
{
    public function __construct(string $slug)
    {
        parent::__construct(
            'La ville {slug} n\'est pas disponible.',
            [
                'slug' => $slug
            ]
        );
    }
}