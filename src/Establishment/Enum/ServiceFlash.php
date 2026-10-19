<?php

namespace App\Establishment\Enum;

use App\Shared\Helper\Translation;

enum ServiceFlash
{
    case CREATED;
    case UPDATED;
    case REACTIVATED;
    case DEACTIVATED;
    case DELETED;
    case DEACTIVATED_INSTEAD;
    case REORDERED;

    public static function toggled(bool $active): self
    {
        if ($active) {
            return self::REACTIVATED;
        }

        return self::DEACTIVATED;
    }

    public static function afterDelete(bool $deleted): self
    {
        if ($deleted) {
            return self::DELETED;
        }

        return self::DEACTIVATED_INSTEAD;
    }

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::CREATED => Translation::translate('Prestation « %s » ajoutée.'),
            self::UPDATED => Translation::translate('Prestation « %s » modifiée.'),
            self::REACTIVATED => Translation::translate('« %s » est de nouveau proposée.'),
            self::DEACTIVATED => Translation::translate('« %s » est désactivée.'),
            self::DELETED => Translation::translate('Prestation supprimée.'),
            self::DEACTIVATED_INSTEAD => Translation::translate('Cette prestation a déjà des créneaux : elle a été désactivée.'),
            self::REORDERED => Translation::translate('Ordre des prestations enregistré.'),
        };
    }
}