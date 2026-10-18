<?php

declare(strict_types=1);

namespace App\Shared\Enum;

use App\Shared\Helper\Translation;
use DateTimeInterface;

enum Weekday: int
{
    case MONDAY = 1;
    case TUESDAY = 2;
    case WEDNESDAY = 3;
    case THURSDAY = 4;
    case FRIDAY = 5;
    case SATURDAY = 6;
    case SUNDAY = 7;

    public static function of(DateTimeInterface $date): self
    {
        return self::from((int) $date->format('N'));
    }

    public function label(): string
    {
        return match ($this) {
            self::MONDAY => Translation::translate('Lundi'),
            self::TUESDAY => Translation::translate('Mardi'),
            self::WEDNESDAY => Translation::translate('Mercredi'),
            self::THURSDAY => Translation::translate('Jeudi'),
            self::FRIDAY => Translation::translate('Vendredi'),
            self::SATURDAY => Translation::translate('Samedi'),
            self::SUNDAY => Translation::translate('Dimanche'),
        };
    }

    public function short(): string
    {
        return match ($this) {
            self::MONDAY => Translation::translate('Lun.'),
            self::TUESDAY => Translation::translate('Mar.'),
            self::WEDNESDAY => Translation::translate('Mer.'),
            self::THURSDAY => Translation::translate('Jeu.'),
            self::FRIDAY => Translation::translate('Ven.'),
            self::SATURDAY => Translation::translate('Sam.'),
            self::SUNDAY => Translation::translate('Dim.'),
        };
    }
}