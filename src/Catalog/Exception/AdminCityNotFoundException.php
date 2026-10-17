<?php

namespace App\Catalog\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class AdminCityNotFoundException extends NotFoundHttpException
{
    public function __construct(string $slug)
    {
        parent::__construct('La ville {slug} n\'existe pas.', ['slug' => $slug]);
    }
}