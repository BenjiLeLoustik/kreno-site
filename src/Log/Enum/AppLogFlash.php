<?php

namespace App\Log\Enum;

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
            self::UNKNOWN_STATUS => 'Statut inconnu.',
            self::STATUS_CHANGED => 'Erreur marquée « %s ».',
            self::DELETED => 'Erreur supprimée.',
            self::PURGED => 'Purge terminée.',
            self::PURGED_ONE => '%d erreur supprimée.',
            self::PURGED_MANY => '%d erreurs supprimées.',
        };
    }
}