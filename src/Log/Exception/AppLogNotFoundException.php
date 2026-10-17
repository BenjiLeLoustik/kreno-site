<?php

namespace App\Log\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class AppLogNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Erreur introuvable.');
    }
}