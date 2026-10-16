<?php

namespace App\Mail\Enum;

enum MailText
{
    case DEFAULT_INVITER;
    case SEVEN_DAYS;
    case ONE_HOUR;
    case THIRTY_MINUTES;
    case TWO_BUSINESS_DAYS;
    case DATE_AT_TIME;
    case HOURS;
    case SLOT_ALERT_TITLE;
    case SLOT_ALERT_BODY;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::DEFAULT_INVITER => 'L’équipe Kréno',
            self::SEVEN_DAYS => '7 jours',
            self::ONE_HOUR => '1 heure',
            self::THIRTY_MINUTES => '30 minutes',
            self::TWO_BUSINESS_DAYS => '48 h ouvrées',
            self::DATE_AT_TIME => '%s à %s',
            self::HOURS => '%d h',
            self::SLOT_ALERT_TITLE => '%s · %s à %s, %s',
            self::SLOT_ALERT_BODY => '%s · %s au lieu de %s',
        };
    }
}