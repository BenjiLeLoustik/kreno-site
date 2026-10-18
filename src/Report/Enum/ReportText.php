<?php

namespace App\Report\Enum;

use App\Shared\Helper\Translation;

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
            self::CUSTOMER => Translation::translate('client'),
            self::A_CUSTOMER => Translation::translate('un client'),
            self::CUSTOMER_NAMED => Translation::translate('client %s'),
            self::KRENO => Translation::translate('Kréno'),
            self::KRENO_TEAM => Translation::translate('Équipe Kréno'),
            self::REPORTED_BY => Translation::translate('Signalé par %s%s · « %s »'),
            self::FIRST_ABOUT_ESTABLISHMENT => Translation::translate('Premier signalement pour cet établissement.'),
            self::FIRST_ABOUT_CUSTOMER => Translation::translate('Premier signalement pour ce client.'),
            self::SIMILAR_ONE => Translation::translate('%d signalement similaire ces %d derniers jours.'),
            self::SIMILAR_MANY => Translation::translate('%d signalements similaires ces %d derniers jours.'),
            self::SUSPEND_ESTABLISHMENT => Translation::translate('Suspendre l\'établissement'),
            self::SUSPEND_CUSTOMER => Translation::translate('Suspendre le client 30 jours'),
            self::DECISION_NOTIFICATION => Translation::translate('Signalement %s : %s'),
        };
    }
}