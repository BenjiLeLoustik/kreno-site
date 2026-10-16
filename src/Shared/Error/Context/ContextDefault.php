<?php

namespace App\Shared\Error\Context;

use Throwable;

class ContextDefault extends AbstractErrorContext
{
    public function supports(Throwable $throwable, int $status): bool
    {
        return $status >= 400;
    }

    public function getCode(): int
    {
        return 400;
    }

    public function getLabel(): string
    {
        return 'demande refusée';
    }

    public function getName(): string
    {
        return 'Demande impossible';
    }

    public function getHint(): string
    {
        return 'Cette action n\'a pas abouti.';
    }

    public function getTitle(): string
    {
        return 'Cette demande n\'a pas abouti.';
    }

    public function getMessage(): string
    {
        return 'Revenez en arrière et réessayez. Si le problème continue, écrivez-nous depuis la page d\'aide.';
    }

    public function hasHelpLink(): bool
    {
        return true;
    }
}