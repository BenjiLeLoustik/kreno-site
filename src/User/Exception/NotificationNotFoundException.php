<?php

namespace App\User\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class NotificationNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Notification introuvable.');
    }
}