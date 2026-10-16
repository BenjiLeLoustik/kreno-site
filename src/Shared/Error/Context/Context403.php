<?php

namespace App\Shared\Error\Context;

use App\Shared\Error\Context\AbstractErrorContext;

class Context403 extends AbstractErrorContext
{

    public function getCode(): int
    {
        return 403;
    }

    public function getLabel(): string
    {
        return 'accès réservé';
    }

    public function getName(): string
    {
        return 'Accès refusé';
    }

    public function getHint(): string
    {
        return 'Ce compte n\'a pas accès à cette page.';
    }

    public function getTitle(): string
    {
        return 'Cette porte est fermée.';
    }

    public function getMessage(): string
    {
        return 'Vous n\'avez pas accès a cette page avec ce compte. Connectez-vous avec le bon compte ou revenez à l\'accueil.';
    }

    public function hasHelpLink(): bool
    {
        return true;
    }
}