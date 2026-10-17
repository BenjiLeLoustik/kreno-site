<?php

namespace App\Establishment\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class ImageNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Photo introuvable.');
    }
}