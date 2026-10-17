<?php

namespace App\User\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class StaffMemberNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Membre introuvable.');
    }
}