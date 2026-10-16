<?php

namespace App\Catalog\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class CityNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Ville introuvable.');
    }
}