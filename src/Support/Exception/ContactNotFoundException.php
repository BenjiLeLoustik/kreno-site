<?php

namespace App\Support\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class ContactNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Cette conversation n\'existe pas.');
    }
}