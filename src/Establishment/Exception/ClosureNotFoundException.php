<?php

namespace App\Establishment\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class ClosureNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Fermeture introuvable.');
    }
}