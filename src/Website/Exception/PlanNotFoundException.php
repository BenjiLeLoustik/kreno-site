<?php

namespace App\Website\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class PlanNotFoundException extends NotFoundHttpException
{
    public function __construct(string $plan)
    {
        parent::__construct('La formule {plan} n\'existe pas.', ['plan' => $plan]);
    }
}