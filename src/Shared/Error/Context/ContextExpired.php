<?php

namespace App\Shared\Error\Context;

use App\Shared\Helper\Translation;
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
        return Translation::translate('page expirée');
    }

    public function getName(): string
    {
        return Translation::translate('Page expirée');
    }

    public function getHint(): string
    {
        return Translation::translate('Le formulaire est resté ouvert trop longtemps.');
    }

    public function getTitle(): string
    {
        return Translation::translate('La page a expiré.');
    }

    public function getMessage(): string
    {
        return Translation::translate('Pour votre sécurité, le formulaire n\'a pas été envoyé. Rechargez la page puis réessayez.');
    }
}