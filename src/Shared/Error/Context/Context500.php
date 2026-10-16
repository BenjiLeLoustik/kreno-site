<?php

namespace App\Shared\Error\Context;

use App\Shared\Error\Context\AbstractErrorContext;
use Throwable;

class Context500 extends AbstractErrorContext
{

    public function supports(Throwable $throwable, int $status): bool
    {
        return $status >= 500;
    }

    public function getCode(): int
    {
        return 500;
    }

    public function getLabel(): string
    {
        return 'imprévu';
    }

    public function getName(): string
    {
        return 'Erreur technique';
    }

    public function getHint(): string
    {
        return 'Nous sommes prévenus.';
    }

    public function getTitle(): string
    {
        return 'Un imprévu de notre côté.';
    }

    public function getMessage(): string
    {
        return 'Réessayez dans quelques instants. Si le problème continue, écrivez-nous depuis la page d\'aide.';
    }

    public function hasHelpLink(): bool
    {
        return true;
    }
}