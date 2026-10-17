<?php

namespace App\Establishment\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class ProSlotNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Créneau introuvable.');
    }
}