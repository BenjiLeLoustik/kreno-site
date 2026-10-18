<?php

namespace App\Website\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class PlanNotFoundException extends NotFoundHttpException
{
    public function __construct(string $plan)
    {
        parent::__construct(
            Translation::translate('La formule {plan} n\'existe pas.'),
            [
                'plan' => $plan
            ]
        );
    }
}