<?php

namespace App\Establishment\Provider;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentSlot;
use App\Enum\Calendar\Weekday;
use App\Establishment\Helper\CalendarHelper;
use App\Establishment\Helper\OpeningHoursHelper;
use App\Establishment\Presenter\SlotPresenter;
use App\Repository\Establishment\EstablishmentSlotRepository;
use DateTimeImmutable;

class AgendaProvider
{
    public function __construct(
        private EstablishmentSlotRepository $slots,
        private SlotPresenter $slotPresenter,
    ) {
    }

    public function week(CompanyEstablishment $establishment, DateTimeImmutable $monday, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $hours = OpeningHoursHelper::byWeekday($establishment->getOpeningHours()->toArray());
        $slots = $this->slots->findBetween($establishment, $monday, $monday->modify('+7 days'));
        [$startHour, $endHour] = OpeningHoursHelper::bounds($establishment->getOpeningHours()->toArray());

        foreach ($slots as $slot) {
            if ($slot->getStartsAt() !== null && $slot->getEndsAt() !== null) {
                $startHour = min($startHour, (int) $slot->getStartsAt()->format('G'));
                $endHour = max($endHour, (int) ceil(CalendarHelper::minutesOfDay($slot->getEndsAt()) / 60));
            }
        }

        $days = [];

        foreach (Weekday::cases() as $weekday) {
            $date = $monday->modify(sprintf('+%d days', $weekday->value - 1));
            $days[] = [
                'weekday' => $weekday->value,
                'name' => mb_substr($weekday->label(), 0, 3),
                'date' => $date->format('j'),
                'iso' => $date->format('Y-m-d'),
                'today' => $date->format('Y-m-d') === $now->format('Y-m-d'),
                'closed' => $hours[$weekday->value] === [],
                'slots' => array_values(array_map(
                    fn (EstablishmentSlot $slot): array => $this->slotPresenter->forAgenda($slot, $startHour, $now),
                    array_filter($slots, static fn (EstablishmentSlot $slot): bool => $slot->getStartsAt()?->format('Y-m-d') === $date->format('Y-m-d')),
                )),
            ];
        }

        return [
            'week' => CalendarHelper::weekCode($monday),
            'weekLabel' => CalendarHelper::weekLabel($monday),
            'previousWeek' => CalendarHelper::weekCode($monday->modify('-7 days')),
            'nextWeek' => CalendarHelper::weekCode($monday->modify('+7 days')),
            'currentWeek' => CalendarHelper::weekCode(CalendarHelper::mondayOf($now)),
            'hours' => array_map(static fn (int $hour): array => ['hour' => $hour, 'label' => $hour . ':00', 'top' => ($hour - $startHour) * 60], range($startHour, max($startHour, $endHour - 1))),
            'startHour' => $startHour,
            'endHour' => $endHour,
            'days' => $days,
            'legend' => [
                ['kind' => 'booked', 'label' => 'Réservé'],
                ['kind' => 'online', 'label' => 'En ligne'],
                ['kind' => 'expired', 'label' => 'Expiré'],
            ],
        ];
    }
}