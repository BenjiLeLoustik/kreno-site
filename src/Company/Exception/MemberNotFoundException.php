<?php

namespace App\Company\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class MemberNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Membre introuvable.');
    }
}