<?php

namespace App\Establishment\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class ClosureNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Fermeture introuvable.')
        );
    }
}