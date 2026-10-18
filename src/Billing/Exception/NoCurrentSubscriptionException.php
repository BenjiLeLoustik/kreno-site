<?php

namespace App\Billing\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class NoCurrentSubscriptionException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Aucun abonnement en cours.')
        );
    }
}