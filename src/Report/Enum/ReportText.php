<?php

namespace App\Report\Enum;

enum ReportText
{
    case NONE;
    case CUSTOMER;
    case A_CUSTOMER;
    case CUSTOMER_NAMED;
    case KRENO;
    case KRENO_TEAM;
    case REPORTED_BY;
    case FIRST_ABOUT_ESTABLISHMENT;
    case FIRST_ABOUT_CUSTOMER;
    case SIMILAR_ONE;
    case SIMILAR_MANY;
    case SUSPEND_ESTABLISHMENT;
    case SUSPEND_CUSTOMER;
    case DECISION_NOTIFICATION;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::NONE => '-',
            self::CUSTOMER => 'client',
            self::A_CUSTOMER => 'un client',
            self::CUSTOMER_NAMED => 'client %s',
            self::KRENO => 'Kréno',
            self::KRENO_TEAM => 'Équipe Kréno',
            self::REPORTED_BY => 'Signalé par %s%s · « %s »',
            self::FIRST_ABOUT_ESTABLISHMENT => 'Premier signalement pour cet établissement.',
            self::FIRST_ABOUT_CUSTOMER => 'Premier signalement pour ce client.',
            self::SIMILAR_ONE => '%d signalement similaire ces %d derniers jours.',
            self::SIMILAR_MANY => '%d signalements similaires ces %d derniers jours.',
            self::SUSPEND_ESTABLISHMENT => 'Suspendre l\'établissement',
            self::SUSPEND_CUSTOMER => 'Suspendre le client 30 jours',
            self::DECISION_NOTIFICATION => 'Signalement %s : %s',
        };
    }
}