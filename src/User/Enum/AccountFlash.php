<?php

namespace App\User\Enum;

enum AccountFlash
{
    case PROFILE_SAVED;
    case PASSWORD_CHANGED;
    case SETTINGS_SAVED;
    case ALERT_CREATED;
    case ALERT_SAVED;
    case ALERT_DELETED;

    public function text(): string
    {
        return match ($this) {
            self::PROFILE_SAVED => 'Informations enregistrées.',
            self::PASSWORD_CHANGED => 'Mot de passe modifié.',
            self::SETTINGS_SAVED => 'Préférences enregistrées.',
            self::ALERT_CREATED => 'Alerte créée : nous vous prévenons dès qu\'un créneau correspond.',
            self::ALERT_SAVED => 'Alerte enregistrée.',
            self::ALERT_DELETED => 'Alerte supprimée.',
        };
    }
}