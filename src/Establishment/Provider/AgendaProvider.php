<?php

namespace App\Establishment\Provider;

use App\Entity\Company\CompanyEstablishment;
use App\Establishment\Enum\SlotKind;
use App\Establishment\Helper\CalendarHelper;
use App\Establishment\Helper\OpeningHoursHelper;
use App\Establishment\Presenter\SlotPresenter;
use App\Repository\Establishment\EstablishmentSlotRepository;
use App\Shared\Enum\Weekday;
use DateTimeImmutable;

class AgendaProvider
{
    public const LEGEND = [
        SlotKind::BOOKED,
        SlotKind::ONLINE,
        SlotKind::EXPIRED
    ];

    public function __construct(
        private EstablishmentSlotRepository $slots,
        private SlotPresenter $slotPresenter,
    ) {
    }

    public function week(
        CompanyEstablishment $establishment,
        DateTimeImmutable $monday,
        ?DateTimeImmutable $now = null,
    ): array {
        $now ??= new DateTimeImmutable();
        $openingHours = $establishment->getOpeningHours()->toArray();
        $slots = $this->slots->findBetween($establishment, $monday, $monday->modify('+7 days'));
        [$startHour, $endHour] = $this->hourBounds($openingHours, $slots);

        return [
            'week' => CalendarHelper::weekCode($monday),
            'weekLabel' => CalendarHelper::weekLabel($monday),
            'previousWeek' => CalendarHelper::weekCode($monday->modify('-7 days')),
            'nextWeek' => CalendarHelper::weekCode($monday->modify('+7 days')),
            'currentWeek' => CalendarHelper::weekCode(CalendarHelper::mondayOf($now)),
            'hours' => $this->hours($startHour, $endHour),
            'startHour' => $startHour,
            'endHour' => $endHour,
            'days' => $this->days($monday, OpeningHoursHelper::byWeekday($openingHours), $slots, $startHour, $now),
            'legend' => $this->legend(),
        ];
    }

    private function hourBounds(array $openingHours, array $slots): array
    {
        [$startHour, $endHour] = OpeningHoursHelper::bounds($openingHours);

        foreach ($slots as $slot) {
            $startsAt = $slot->getStartsAt();
            $endsAt = $slot->getEndsAt();

            if ($startsAt === null || $endsAt === null) {
                continue;
            }

            $startHour = min($startHour, (int) $startsAt->format('G'));
            $endHour = max($endHour, (int) ceil(CalendarHelper::minutesOfDay($endsAt) / 60));
        }

        return [
            $startHour,
            $endHour
        ];
    }

    private function hours(int $startHour, int $endHour): array
    {
        $hours = [];

        foreach (range($startHour, max($startHour, $endHour - 1)) as $hour) {
            $hours[] = [
                'hour' => $hour,
                'label' => $hour . ':00',
                'top' => ($hour - $startHour) * 60
            ];
        }

        return $hours;
    }

    private function days(
        DateTimeImmutable $monday,
        array $openingHours,
        array $slots,
        int $startHour,
        DateTimeImmutable $now,
    ): array {
        $days = [];

        foreach (Weekday::cases() as $weekday) {
            $date = $monday->modify(sprintf('+%d days', $weekday->value - 1));

            $days[] = [
                'weekday' => $weekday->value,
                'name' => mb_substr($weekday->label(), 0, 3),
                'date' => $date->format('j'),
                'iso' => $date->format('Y-m-d'),
                'today' => $date->format('Y-m-d') === $now->format('Y-m-d'),
                'closed' => $openingHours[$weekday->value] === [],
                'slots' => $this->daySlots($slots, $date, $startHour, $now),
            ];
        }

        return $days;
    }

    private function daySlots(array $slots, DateTimeImmutable $date, int $startHour, DateTimeImmutable $now): array
    {
        $day = $date->format('Y-m-d');
        $result = [];

        foreach ($slots as $slot) {
            if ($slot->getStartsAt()?->format('Y-m-d') === $day) {
                $result[] = $this->slotPresenter->forAgenda($slot, $startHour, $now);
            }
        }

        return $result;
    }

    private function legend(): array
    {
        $legend = [];

        foreach (self::LEGEND as $kind) {
            $legend[] = [
                'kind' => $kind->value,
                'label' => $kind->label()
            ];
        }

        return $legend;
    }
}