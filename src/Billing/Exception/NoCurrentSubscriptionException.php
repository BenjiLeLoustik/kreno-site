<?php

namespace App\Billing\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class NoCurrentSubscriptionException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Aucun abonnement en cours.');
    }
}