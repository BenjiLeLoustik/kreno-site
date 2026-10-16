<?php

namespace App\Establishment\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class SlotNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Ce créneau n\'existe pas.');
    }
}