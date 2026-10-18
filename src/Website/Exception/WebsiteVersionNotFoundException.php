<?php

namespace App\Website\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class WebsiteVersionNotFoundException extends NotFoundHttpException
{
    public function __construct(?string $code)
    {
        parent::__construct(
            Translation::translate('La version {code} n\'existe pas.'),
            [
                'code' => $code ?? Translation::translate('en ligne')
            ]
        );
    }
}