<?php

namespace App\Support\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class AdminContactNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Demande introuvable.');
    }
}