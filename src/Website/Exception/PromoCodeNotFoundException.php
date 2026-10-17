<?php

namespace App\Website\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class PromoCodeNotFoundException extends NotFoundHttpException
{
    public function __construct(string $promo)
    {
        parent::__construct('Le code {promo} n\'existe pas.', ['promo' => $promo]);
    }
}