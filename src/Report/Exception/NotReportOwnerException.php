<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;

class NotReportOwnerException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce signalement ne vous appartient pas.');
    }
}