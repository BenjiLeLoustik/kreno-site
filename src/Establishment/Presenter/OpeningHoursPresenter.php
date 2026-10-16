<?php

namespace App\Establishment\Presenter;

use App\Entity\Establishment\EstablishmentClosure;
use App\Enum\Calendar\Weekday;
use App\Establishment\Helper\OpeningHoursHelper;
use App\Shared\Helper\DateHelper;
use DateTimeImmutable;

class OpeningHoursPresenter
{
    public function week(array $hours): array
    {
        $week = OpeningHoursHelper::byWeekday($hours);

        return array_map(static fn (Weekday $weekday): array => [
            'weekday' => $weekday->value,
            'day' => $weekday->label(),
            'ranges' => array_map(static fn (array $range): array => ['opensAt' => $range[0], 'closesAt' => $range[1]], $week[$weekday->value]),
            'hours' => OpeningHoursHelper::rangesLabel($week[$weekday->value]),
            'closed' => $week[$weekday->value] === [],
        ], Weekday::cases());
    }

    public function grouped(array $hours): array
    {
        return OpeningHoursHelper::group($hours);
    }

    public function status(array $hours, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();

        return [
            'open' => OpeningHoursHelper::isOpenAt($hours, $now),
            'label' => OpeningHoursHelper::status($hours, $now),
        ];
    }

    public function closure(EstablishmentClosure $closure): array
    {
        $sameDay = $closure->getStartsOn()?->format('Y-m-d') === $closure->getEndsOn()?->format('Y-m-d');

        return [
            'id' => $closure->getId(),
            'startsOn' => DateHelper::day($closure->getStartsOn()),
            'endsOn' => DateHelper::day($closure->getEndsOn()),
            'reason' => $closure->getReason(),
            'label' => $sameDay
                ? sprintf('Le %s', $closure->getStartsOn()?->format('d/m/Y'))
                : sprintf('Du %s au %s', $closure->getStartsOn()?->format('d/m/Y'), $closure->getEndsOn()?->format('d/m/Y')),
        ];
    }

    public function closures(array $closures): array
    {
        return array_values(array_map($this->closure(...), $closures));
    }
}