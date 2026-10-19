<?php

namespace App\Catalog\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class AdminCityNotFoundException extends NotFoundHttpException
{
    public function __construct(string $slug)
    {
        parent::__construct(
            Translation::translate('La ville {slug} n\'existe pas.'),
            [
                'slug' => $slug
            ]
        );
    }
}