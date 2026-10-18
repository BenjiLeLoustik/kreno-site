<?php

namespace App\Shared\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class LocaleNotFoundException extends NotFoundHttpException
{
    public function __construct(string $locale)
    {
        parent::__construct(
            Translation::translate('La langue {locale} n\'est pas disponible.'),
            [
                'locale' => $locale
            ]
        );
    }
}