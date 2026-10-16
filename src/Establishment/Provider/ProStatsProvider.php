<?php

namespace App\Establishment\Provider;

use App\Booking\Enum\BookingStatus;
use App\Entity\Booking;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentSlot;
use App\Establishment\Helper\CalendarHelper;
use App\Repository\BookingRepository;
use App\Repository\BookingStatsRepository;
use App\Repository\Establishment\EstablishmentSlotRepository;
use App\Shared\Helper\DurationHelper;
use App\Shared\Helper\MoneyHelper;
use DateTimeImmutable;

class ProStatsProvider
{
    public const PERIODS = [7 => '7 jours', 30 => '30 jours', 90 => '90 jours'];

    public const DEFAULT_PERIOD = 30;

    public const WEEKS = 8;

    public const HOURS = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19];

    public const TOP_SERVICES = 4;

    private const EARNED_STATUSES = [BookingStatus::CONFIRMED, BookingStatus::HONORED];

    public function __construct(
        private EstablishmentSlotRepository $slots,
        private BookingStatsRepository $stats,
    ) {
    }

    public function build(CompanyEstablishment $establishment, int $period, bool $advanced, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $period = isset(self::PERIODS[$period]) ? $period : self::DEFAULT_PERIOD;
        $from = $now->modify(sprintf('-%d days', $period));
        $current = $this->slots->findBetween($establishment, $from, $now);
        $previous = $this->slots->findBetween($establishment, $from->modify(sprintf('-%d days', $period)), $from);

        return [
            'period' => $period,
            'periods' => array_map(static fn (int $days, string $label): array => ['value' => $days, 'label' => $label, 'selected' => $days === $period], array_keys(self::PERIODS), self::PERIODS),
            'kpis' => $this->kpis($establishment, $current, $previous, $from),
            'advanced' => $advanced,
            'weeks' => $advanced ? $this->weeks($establishment, $now) : [],
            'services' => $advanced ? $this->services($current) : [],
            'heatmap' => $advanced ? $this->heatmap($current) : null,
        ];
    }

    private function kpis(CompanyEstablishment $establishment, array $current, array $previous, DateTimeImmutable $from): array
    {
        $rate = $this->fillRate($current);
        $previousRate = $this->fillRate($previous);
        $earned = array_filter(array_map(fn (EstablishmentSlot $slot): ?Booking => $this->booking($slot, self::EARNED_STATUSES), $current));
        $delays = array_filter(array_map(fn (EstablishmentSlot $slot): ?int => $this->delay($slot), $current), static fn (?int $delay): bool => $delay !== null);
        $newCustomers = $this->newCustomers($establishment, $current, $from);

        return [
            [
                'key' => 'fill',
                'label' => 'Taux de remplissage',
                'value' => $rate !== null ? sprintf('%d %%', $rate) : '—',
                'note' => $rate !== null && $previousRate !== null ? sprintf('%s%d pts', $rate - $previousRate >= 0 ? '+' : '−', abs($rate - $previousRate)) : 'sur la période précédente',
            ],
            [
                'key' => 'revenue',
                'label' => 'Chiffre récupéré',
                'value' => MoneyHelper::format(array_sum(array_map(static fn (Booking $booking): int => $booking->getPrice(), $earned))),
                'note' => sprintf('sur %d créneau%s', count($earned), count($earned) > 1 ? 'x' : ''),
            ],
            [
                'key' => 'delay',
                'label' => 'Délai moyen de réservation',
                'value' => $delays !== [] ? DurationHelper::format((int) round(array_sum($delays) / count($delays))) : '—',
                'note' => 'après publication',
            ],
            [
                'key' => 'customers',
                'label' => 'Nouveaux clients',
                'value' => (string) $newCustomers,
                'note' => 'venus par Kréno',
            ],
        ];
    }

    private function weeks(CompanyEstablishment $establishment, DateTimeImmutable $now): array
    {
        $start = CalendarHelper::mondayOf($now)->modify(sprintf('-%d weeks', self::WEEKS - 1));
        $weeks = [];

        for ($i = 0; $i < self::WEEKS; ++$i) {
            $monday = $start->modify(sprintf('+%d weeks', $i));
            $weeks[$monday->format('Y-m-d')] = ['label' => 'S' . (int) $monday->format('W'), 'published' => 0, 'booked' => 0];
        }

        foreach ($this->slots->findBetween($establishment, $start, $now) as $slot) {
            $key = CalendarHelper::mondayOf($slot->getStartsAt() ?? $now)->format('Y-m-d');

            if (!isset($weeks[$key])) {
                continue;
            }

            ++$weeks[$key]['published'];

            if ($this->booking($slot, BookingRepository::KEPT_STATUSES) !== null) {
                ++$weeks[$key]['booked'];
            }
        }

        $max = max([1, ...array_map(static fn (array $week): int => $week['published'], array_values($weeks))]);

        return array_values(array_map(static fn (array $week): array => $week + [
                'publishedPercent' => (int) round($week['published'] / $max * 100),
                'bookedPercent' => (int) round($week['booked'] / $max * 100),
                'title' => sprintf('%d publié%s, %d réservé%s', $week['published'], $week['published'] > 1 ? 's' : '', $week['booked'], $week['booked'] > 1 ? 's' : ''),
            ], $weeks));
    }

    private function services(array $slots): array
    {
        $counts = [];

        foreach ($slots as $slot) {
            $name = $slot->getService()?->getName();

            if ($name !== null && $this->booking($slot, BookingRepository::KEPT_STATUSES) !== null) {
                $counts[$name] = ($counts[$name] ?? 0) + 1;
            }
        }

        arsort($counts);
        $counts = array_slice($counts, 0, self::TOP_SERVICES, true);
        $max = max([1, ...array_values($counts)]);

        return array_map(static fn (string $name, int $count): array => [
            'name' => $name,
            'count' => $count,
            'label' => sprintf('%d réservation%s', $count, $count > 1 ? 's' : ''),
            'percent' => (int) round($count / $max * 100),
        ], array_keys($counts), array_values($counts));
    }

    private function heatmap(array $slots): array
    {
        $cells = [];

        foreach ($slots as $slot) {
            $delay = $this->delay($slot);
            $startsAt = $slot->getStartsAt();

            if ($delay === null || $startsAt === null) {
                continue;
            }

            $cells[(int) $startsAt->format('N')][(int) $startsAt->format('G')][] = $delay;
        }

        $rows = [];
        $best = null;

        foreach (CalendarHelper::SHORT_WEEKDAYS as $day => $label) {
            $row = ['day' => $label, 'cells' => []];

            foreach (self::HOURS as $hour) {
                $delays = $cells[$day][$hour] ?? [];
                $average = $delays !== [] ? (int) round(array_sum($delays) / count($delays)) : null;
                $level = $this->level($average);
                $row['cells'][] = [
                    'hour' => $hour,
                    'level' => $level,
                    'count' => count($delays),
                    'title' => $average !== null ? sprintf('%s %dh · réservé en %s en moyenne', $label, $hour, DurationHelper::format($average)) : sprintf('%s %dh · pas de donnée', $label, $hour),
                ];

                if ($level > 0 && ($best === null || [$level, count($delays)] > [$best['level'], $best['count']])) {
                    $best = ['day' => CalendarHelper::WEEKDAYS[$day], 'hour' => $hour, 'level' => $level, 'count' => count($delays)];
                }
            }

            $rows[] = $row;
        }

        return [
            'hours' => array_map(static fn (int $hour): string => $hour . 'h', self::HOURS),
            'rows' => $rows,
            'tip' => $best !== null ? sprintf('Vos créneaux partent le plus vite le %s vers %dh : publiez en priorité à ce moment-là.', $best['day'], $best['hour']) : null,
        ];
    }

    private function newCustomers(CompanyEstablishment $establishment, array $slots, DateTimeImmutable $from): int
    {
        $customers = [];

        foreach ($slots as $slot) {
            $customer = $this->booking($slot, BookingRepository::KEPT_STATUSES)?->getCustomer();

            if ($customer?->getId() !== null) {
                $customers[$customer->getId()] = $customer->getId();
            }
        }

        return count(array_diff($customers, $this->stats->returningCustomerIds($establishment, $customers, $from)));
    }

    private function fillRate(array $slots): ?int
    {
        if ($slots === []) {
            return null;
        }

        $booked = array_filter($slots, fn (EstablishmentSlot $slot): bool => $this->booking($slot, BookingRepository::KEPT_STATUSES) !== null);

        return (int) round(count($booked) / count($slots) * 100);
    }

    private function delay(EstablishmentSlot $slot): ?int
    {
        $booking = $this->booking($slot, BookingRepository::KEPT_STATUSES);

        if ($booking?->getCreatedAt() === null || $slot->getCreatedAt() === null) {
            return null;
        }

        return max(0, intdiv($booking->getCreatedAt()->getTimestamp() - $slot->getCreatedAt()->getTimestamp(), 60));
    }

    private function booking(EstablishmentSlot $slot, array $statuses): ?Booking
    {
        foreach ($slot->getBookings() as $booking) {
            if (in_array($booking->getStatus(), $statuses, true)) {
                return $booking;
            }
        }

        return null;
    }

    private function level(?int $minutes): int
    {
        return match (true) {
            $minutes === null => 0,
            $minutes <= 15 => 4,
            $minutes <= 30 => 3,
            $minutes <= 60 => 2,
            default => 1,
        };
    }
}