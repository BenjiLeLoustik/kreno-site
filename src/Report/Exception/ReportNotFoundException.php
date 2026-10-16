<?php

namespace App\Report\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class ReportNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Signalement introuvable.');
    }
}