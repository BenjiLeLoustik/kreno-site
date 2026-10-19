<?php

namespace App\User\Enum;

use App\Shared\Helper\Translation;

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
            self::PROFILE_SAVED => Translation::translate('Informations enregistrées.'),
            self::PASSWORD_CHANGED => Translation::translate('Mot de passe modifié.'),
            self::SETTINGS_SAVED => Translation::translate('Préférences enregistrées.'),
            self::ALERT_CREATED => Translation::translate('Alerte créée : nous vous prévenons dès qu\'un créneau correspond.'),
            self::ALERT_SAVED => Translation::translate('Alerte enregistrée.'),
            self::ALERT_DELETED => Translation::translate('Alerte supprimée.'),
        };
    }
}