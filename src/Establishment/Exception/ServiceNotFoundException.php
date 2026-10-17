<?php

namespace App\Establishment\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class ServiceNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Prestation introuvable.');
    }
}