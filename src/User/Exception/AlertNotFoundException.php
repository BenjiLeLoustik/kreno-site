<?php

namespace App\User\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class AlertNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Alerte introuvable.');
    }
}