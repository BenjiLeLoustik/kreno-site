<?php

namespace App\Log\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class AppLogNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Erreur introuvable.')
        );
    }
}