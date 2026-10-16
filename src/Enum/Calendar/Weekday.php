<?php

declare(strict_types=1);

namespace App\Enum\Calendar;

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
            self::MONDAY => 'Lundi',
            self::TUESDAY => 'Mardi',
            self::WEDNESDAY => 'Mercredi',
            self::THURSDAY => 'Jeudi',
            self::FRIDAY => 'Vendredi',
            self::SATURDAY => 'Samedi',
            self::SUNDAY => 'Dimanche',
        };
    }

    public function short(): string
    {
        return match ($this) {
            self::MONDAY => 'Lun.',
            self::TUESDAY => 'Mar.',
            self::WEDNESDAY => 'Mer.',
            self::THURSDAY => 'Jeu.',
            self::FRIDAY => 'Ven.',
            self::SATURDAY => 'Sam.',
            self::SUNDAY => 'Dim.',
        };
    }
}