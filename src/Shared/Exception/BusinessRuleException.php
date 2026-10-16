<?php

namespace App\Shared\Exception;

use NeoPHP\Component\Http\Exception\HttpException;

class BusinessRuleException extends HttpException
{
    public function __construct(string $message, array $context = [])
    {
        parent::__construct(422, $message, [], $context);
    }
}