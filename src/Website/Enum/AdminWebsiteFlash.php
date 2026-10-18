<?php

namespace App\Website\Enum;

use App\Shared\Helper\Translation;
use DateTimeImmutable;

enum AdminWebsiteFlash
{
    case DUPLICATED;
    case IDENTITY_SAVED;
    case SCHEDULED;
    case UNSCHEDULED;
    case PUBLISHED;
    case RESTORED;
    case DELETED;

    public const SCHEDULE_FORMAT = 'd/m/Y à H:i';

    public static function scheduled(DateTimeImmutable $date): string
    {
        return self::SCHEDULED->text($date->format(self::SCHEDULE_FORMAT));
    }

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::DUPLICATED => Translation::translate('Version %s dupliquée en brouillon.'),
            self::IDENTITY_SAVED => Translation::translate('Identité de la version enregistrée.'),
            self::SCHEDULED => Translation::translate('Mise en ligne planifiée le %s.'),
            self::UNSCHEDULED => Translation::translate('Planification annulée. La version repasse en brouillon.'),
            self::PUBLISHED => Translation::translate('Version %s mise en ligne.'),
            self::RESTORED => Translation::translate('Retour à la version %s : sa copie est en ligne.'),
            self::DELETED => Translation::translate('Version %s supprimée.'),
        };
    }
}