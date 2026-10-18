<?php

namespace App\Shared\Error\Context;

use App\Shared\Error\Context\AbstractErrorContext;
use App\Shared\Helper\Translation;

class Context403 extends AbstractErrorContext
{

    public function getCode(): int
    {
        return 403;
    }

    public function getLabel(): string
    {
        return Translation::translate('accès réservé');
    }

    public function getName(): string
    {
        return Translation::translate('Accès refusé');
    }

    public function getHint(): string
    {
        return Translation::translate('Ce compte n\'a pas accès à cette page.');
    }

    public function getTitle(): string
    {
        return Translation::translate('Cette porte est fermée.');
    }

    public function getMessage(): string
    {
        return Translation::translate('Vous n\'avez pas accès a cette page avec ce compte. Connectez-vous avec le bon compte ou revenez à l\'accueil.');
    }

    public function hasHelpLink(): bool
    {
        return true;
    }
}