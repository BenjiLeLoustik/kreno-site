<?php

namespace App\Shared\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class LocaleNotFoundException extends NotFoundHttpException
{
    public function __construct(string $locale)
    {
        parent::__construct('La langue {locale} n\'est pas disponible.', ['locale' => $locale]);
    }
}