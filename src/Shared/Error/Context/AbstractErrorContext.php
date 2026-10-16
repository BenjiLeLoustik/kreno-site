<?php

namespace App\Shared\Error\Context;

use App\Shared\Error\Context\ErrorContextInterface;
use Throwable;

class AbstractErrorContext implements ErrorContextInterface
{

    public function supports(Throwable $throwable, int $status): bool
    {
        return $status === $this->getCode();
    }

    public function hasHelpLink(): bool
    {
        return false;
    }

    public function getCode(): int
    {
        return 0;
    }

    public function getLabel(): string
    {
        return '';
    }

    public function getName(): string
    {
        return '';
    }

    public function getHint(): string
    {
        return '';
    }

    public function getTitle(): string
    {
        return '';
    }

    public function getMessage(): string
    {
        return '';
    }
}