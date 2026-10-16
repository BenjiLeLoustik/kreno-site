<?php

declare(strict_types=1);

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
}