<?php

namespace App\Shared\Error\Context;

use NeoPHP\Component\Csrf\Exception\InvalidCsrfTokenException;
use Throwable;

class ContextExpired extends AbstractErrorContext
{
    public function supports(Throwable $throwable, int $status): bool
    {
        return $throwable instanceof InvalidCsrfTokenException;
    }

    public function getCode(): int
    {
        return 403;
    }

    public function getLabel(): string
    {
        return 'page expirée';
    }

    public function getName(): string
    {
        return 'Page expirée';
    }

    public function getHint(): string
    {
        return 'Le formulaire est resté ouvert trop longtemps.';
    }

    public function getTitle(): string
    {
        return 'La page a expiré.';
    }

    public function getMessage(): string
    {
        return 'Pour votre sécurité, le formulaire n\'a pas été envoyé. Rechargez la page puis réessayez.';
    }
}