<?php

namespace App\Shared\Error\Context;

use App\Shared\Error\Context\AbstractErrorContext;

class Context404 extends AbstractErrorContext
{

    public function getCode(): int
    {
        return 404;
    }

    public function getLabel(): string
    {
        return 'créneau perdu';
    }

    public function getName(): string
    {
        return 'Page introuvable';
    }

    public function getHint(): string
    {
        return 'Ce lien a expiré ou n\'a jamais existé.';
    }

    public function getTitle(): string
    {
        return 'Ce créneau-là est déjà parti.';
    }

    public function getMessage(): string
    {
        return 'La page de vous cherchez n\'existe pas ou plus. Un créneau réservé ou expiré disparaît aussi du site.';
    }

}