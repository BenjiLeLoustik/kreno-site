<?php

namespace App\Report\Enum;

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
            self::SENT => 'Envoyé',
            self::IN_REVIEW => 'En cours d\'examen',
            self::AWAITING_REPLY => 'Réponse attendue',
            self::RESOLVED => 'Traité',
            self::DISMISSED => 'Classé',
        };
    }

    public function customerLabel(): string
    {
        return match ($this) {
            self::SENT => 'Envoyé',
            self::IN_REVIEW, self::AWAITING_REPLY => 'En cours',
            self::RESOLVED => 'Résolu',
            self::DISMISSED => 'Classé',
        };
    }

    public function adminLabel(): string
    {
        return match ($this) {
            self::SENT, self::IN_REVIEW => 'Ouvert',
            self::AWAITING_REPLY => 'En attente de l’établissement',
            self::RESOLVED => 'Traité',
            self::DISMISSED => 'Classé',
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