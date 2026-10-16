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
        $days = [];

        foreach (Weekday::cases() as $weekday) {
            $ranges = $week[$weekday->value];

            $days[] = [
                'weekday' => $weekday->value,
                'day' => $weekday->label(),
                'ranges' => $this->ranges($ranges),
                'hours' => OpeningHoursHelper::rangesLabel($ranges),
                'closed' => $ranges === [],
            ];
        }

        return $days;
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
        return [
            'id' => $closure->getId(),
            'startsOn' => DateHelper::day($closure->getStartsOn()),
            'endsOn' => DateHelper::day($closure->getEndsOn()),
            'reason' => $closure->getReason(),
            'label' => $this->closureLabel($closure),
        ];
    }

    public function closures(array $closures): array
    {
        return array_values(array_map($this->closure(...), $closures));
    }

    private function ranges(array $ranges): array
    {
        $result = [];

        foreach ($ranges as [$opensAt, $closesAt]) {
            $result[] = ['opensAt' => $opensAt, 'closesAt' => $closesAt];
        }

        return $result;
    }

    private function closureLabel(EstablishmentClosure $closure): string
    {
        $startsOn = $closure->getStartsOn()?->format('d/m/Y');
        $endsOn = $closure->getEndsOn()?->format('d/m/Y');

        if ($startsOn === $endsOn) {
            return sprintf('Le %s', $startsOn);
        }

        return sprintf('Du %s au %s', $startsOn, $endsOn);
    }
}