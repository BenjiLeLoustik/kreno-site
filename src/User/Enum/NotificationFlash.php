<?php

namespace App\User\Enum;

use App\Shared\Helper\Translation;

enum NotificationFlash
{
    case ALL_READ;
    case NOTHING_UNREAD;

    public static function afterReadAll(int $count): self
    {
        if ($count > 0) {
            return self::ALL_READ;
        }

        return self::NOTHING_UNREAD;
    }

    public function text(): string
    {
        return match ($this) {
            self::ALL_READ => Translation::translate('Toutes les notifications sont marquées comme lues.'),
            self::NOTHING_UNREAD => Translation::translate('Aucune notification non lue.'),
        };
    }
}