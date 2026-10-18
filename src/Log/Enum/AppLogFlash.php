<?php

namespace App\Log\Enum;

use App\Shared\Helper\Translation;

enum AppLogFlash
{
    case UNKNOWN_STATUS;
    case STATUS_CHANGED;
    case DELETED;
    case PURGED;
    case PURGED_ONE;
    case PURGED_MANY;

    public static function statusChanged(AppLogStatus $status): string
    {
        return self::STATUS_CHANGED->text(mb_strtolower($status->label()));
    }

    public static function purgedCount(int $count): string
    {
        if ($count > 1) {
            return self::PURGED_MANY->text($count);
        }

        return self::PURGED_ONE->text($count);
    }

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::UNKNOWN_STATUS => Translation::translate('Statut inconnu.'),
            self::STATUS_CHANGED => Translation::translate('Erreur marquée « %s ».'),
            self::DELETED => Translation::translate('Erreur supprimée.'),
            self::PURGED => Translation::translate('Purge terminée.'),
            self::PURGED_ONE => Translation::translate('%d erreur supprimée.'),
            self::PURGED_MANY => Translation::translate('%d erreurs supprimées.'),
        };
    }
}