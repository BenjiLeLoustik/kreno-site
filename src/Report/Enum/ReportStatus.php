<?php

namespace App\Report\Enum;

use App\Shared\Helper\Translation;

enum ReportStatus: string
{
    case SENT = 'sent';
    case IN_REVIEW = 'in_review';
    case AWAITING_REPLY = 'awaiting_reply';
    case RESOLVED = 'resolved';
    case DISMISSED = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::SENT => Translation::translate('Envoyé'),
            self::IN_REVIEW => Translation::translate('En cours d\'examen'),
            self::AWAITING_REPLY => Translation::translate('Réponse attendue'),
            self::RESOLVED => Translation::translate('Traité'),
            self::DISMISSED => Translation::translate('Classé'),
        };
    }

    public function customerLabel(): string
    {
        return match ($this) {
            self::SENT => Translation::translate('Envoyé'),
            self::IN_REVIEW,
            self::AWAITING_REPLY => Translation::translate('En cours'),
            self::RESOLVED => Translation::translate('Résolu'),
            self::DISMISSED => Translation::translate('Classé'),
        };
    }

    public function adminLabel(): string
    {
        return match ($this) {
            self::SENT, self::IN_REVIEW => Translation::translate('Ouvert'),
            self::AWAITING_REPLY => Translation::translate('En attente de l’établissement'),
            self::RESOLVED => Translation::translate('Traité'),
            self::DISMISSED => Translation::translate('Classé'),
        };
    }

    public function kind(): string
    {
        return match ($this) {
            self::SENT => 'sent',
            self::IN_REVIEW => 'review',
            self::AWAITING_REPLY => 'answer',
            self::RESOLVED => 'done',
            self::DISMISSED => 'closed',
        };
    }

    public function isOpen(): bool
    {
        return $this !== self::RESOLVED && $this !== self::DISMISSED;
    }
}