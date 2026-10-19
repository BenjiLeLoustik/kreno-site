<?php

namespace App\Website\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class PromoCodeNotFoundException extends NotFoundHttpException
{
    public function __construct(string $promo)
    {
        parent::__construct(
            Translation::translate('Le code {promo} n\'existe pas.'),
            [
                'promo' => $promo
            ]
        );
    }
}