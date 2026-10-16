<?php

namespace App\Establishment\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class EstablishmentNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Établissement introuvable.');
    }
}