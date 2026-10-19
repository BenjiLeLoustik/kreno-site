<?php

namespace App\Shared\Error\Context;

use Throwable;

interface ErrorContextInterface
{
    public function supports(Throwable $throwable, int $status): bool;

    public function getCode(): int;

    public function getLabel(): string;

    public function getName(): string;

    public function getHint(): string;

    public function getTitle(): string;

    public function getMessage(): string;

    public function hasHelpLink(): bool;
}