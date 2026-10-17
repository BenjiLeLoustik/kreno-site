<?php

namespace App\Company\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class InvitationNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Invitation introuvable.');
    }
}