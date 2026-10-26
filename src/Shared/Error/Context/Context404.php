<?php

namespace App\Shared\Error\Context;

use App\Shared\Error\Context\AbstractErrorContext;
use App\Shared\Helper\Translation;

class Context404 extends AbstractErrorContext
{

    public function getCode(): int
    {
        return 404;
    }

    public function getLabel(): string
    {
        return Translation::translate('créneau perdu');
    }

    public function getName(): string
    {
        return Translation::translate('Page introuvable');
    }

    public function getHint(): string
    {
        return Translation::translate('Ce lien a expiré ou n\'a jamais existé.');
    }

    public function getTitle(): string
    {
        return Translation::translate('Ce créneau-là est déjà parti.');
    }

    public function getMessage(): string
    {
        return Translation::translate('La page que vous cherchez n\'existe pas ou plus. Un créneau réservé ou expiré disparaît aussi du site.');
    }

}