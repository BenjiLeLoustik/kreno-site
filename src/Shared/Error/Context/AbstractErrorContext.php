<?php

namespace App\Shared\Error\Context;

use App\Shared\Error\Context\ErrorContextInterface;
use Throwable;

abstract class AbstractErrorContext implements ErrorContextInterface
{

    public function supports(Throwable $throwable, int $status): bool
    {
        return $status === $this->getCode();
    }

    public function hasHelpLink(): bool
    {
        return false;
    }
}