<?php

namespace App\Enum\Calendar;

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
            self::JANUARY => 'janvier',
            self::FEBRUARY => 'février',
            self::MARCH => 'mars',
            self::APRIL => 'avril',
            self::MAY => 'mai',
            self::JUNE => 'juin',
            self::JULY => 'juillet',
            self::AUGUST => 'août',
            self::SEPTEMBER => 'septembre',
            self::OCTOBER => 'octobre',
            self::NOVEMBER => 'novembre',
            self::DECEMBER => 'décembre',
        };
    }

    public function short(): string
    {
        return match ($this) {
            self::JANUARY => 'janv.',
            self::FEBRUARY => 'févr.',
            self::MARCH => 'mars',
            self::APRIL => 'avr.',
            self::MAY => 'mai',
            self::JUNE => 'juin',
            self::JULY => 'juil.',
            self::AUGUST => 'août',
            self::SEPTEMBER => 'sept.',
            self::OCTOBER => 'oct.',
            self::NOVEMBER => 'nov.',
            self::DECEMBER => 'déc.',
        };
    }
}