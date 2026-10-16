<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class MemberOutsideScopeException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce membre travaille aussi dans un établissement que vous ne gérez pas.');
    }
}