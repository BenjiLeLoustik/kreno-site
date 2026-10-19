<?php

namespace App\Shared\Enum;

use App\Shared\Helper\Translation;
use DateTimeInterface;

enum Month: int
{
    case JANUARY = 1;
    case FEBRUARY = 2;
    case MARCH = 3;
    case APRIL = 4;
    case MAY = 5;
    case JUNE = 6;
    case JULY = 7;
    case AUGUST = 8;
    case SEPTEMBER = 9;
    case OCTOBER = 10;
    case NOVEMBER = 11;
    case DECEMBER = 12;

    public static function of(DateTimeInterface $date): self
    {
        return self::from((int) $date->format('n'));
    }

    public function label(): string
    {
        return match ($this) {
            self::JANUARY => Translation::translate('janvier'),
            self::FEBRUARY => Translation::translate('février'),
            self::MARCH => Translation::translate('mars'),
            self::APRIL => Translation::translate('avril'),
            self::MAY => Translation::translate('mai'),
            self::JUNE => Translation::translate('juin'),
            self::JULY => Translation::translate('juillet'),
            self::AUGUST => Translation::translate('août'),
            self::SEPTEMBER => Translation::translate('septembre'),
            self::OCTOBER => Translation::translate('octobre'),
            self::NOVEMBER => Translation::translate('novembre'),
            self::DECEMBER => Translation::translate('décembre'),
        };
    }

    public function short(): string
    {
        return match ($this) {
            self::JANUARY => Translation::translate('janv.'),
            self::FEBRUARY => Translation::translate('févr.'),
            self::MARCH => Translation::translate('mars'),
            self::APRIL => Translation::translate('avr.'),
            self::MAY => Translation::translate('mai'),
            self::JUNE => Translation::translate('juin'),
            self::JULY => Translation::translate('juil.'),
            self::AUGUST => Translation::translate('août'),
            self::SEPTEMBER => Translation::translate('sept.'),
            self::OCTOBER => Translation::translate('oct.'),
            self::NOVEMBER => Translation::translate('nov.'),
            self::DECEMBER => Translation::translate('déc.'),
        };
    }
}