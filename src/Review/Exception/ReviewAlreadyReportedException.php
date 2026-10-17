<?php

namespace App\Review\Exception;

use App\Shared\Exception\BusinessRuleException;

class ReviewAlreadyReportedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cet avis est déjà en cours d\'examen par notre équipe.');
    }
}