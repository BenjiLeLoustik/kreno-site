<?php

namespace App\Establishment\Enum;

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
            self::CREATED => 'Prestation « %s » ajoutée.',
            self::UPDATED => 'Prestation « %s » modifiée.',
            self::REACTIVATED => '« %s » est de nouveau proposée.',
            self::DEACTIVATED => '« %s » est désactivée.',
            self::DELETED => 'Prestation supprimée.',
            self::DEACTIVATED_INSTEAD => 'Cette prestation a déjà des créneaux : elle a été désactivée.',
            self::REORDERED => 'Ordre des prestations enregistré.',
        };
    }
}