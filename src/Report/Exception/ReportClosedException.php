<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;

class ReportClosedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce signalement est clos.');
    }
}