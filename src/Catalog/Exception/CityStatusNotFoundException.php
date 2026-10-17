<?php

namespace App\Catalog\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class CityStatusNotFoundException extends NotFoundHttpException
{
    public function __construct(string $status)
    {
        parent::__construct('Statut {status} inconnu.', ['status' => $status]);
    }
}