<?php

declare(strict_types=1);

namespace App\Shared\Helper;

use DateTimeImmutable;
use DateTimeInterface;

class DateHelper
{
    public static function iso(?DateTimeInterface $date): ?string
    {
        return $date?->format(DateTimeInterface::ATOM);
    }

    public static function day(?DateTimeInterface $date): ?string
    {
        return $date?->format('Y-m-d');
    }

    public static function time(?DateTimeInterface $date): ?string
    {
        return $date?->format('H:i');
    }

    public static function parse(?string $value): ?DateTimeImmutable
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $value)
            ?: DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value);

        return $date ?: null;
    }
}