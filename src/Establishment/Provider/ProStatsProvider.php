<?php

namespace App\Establishment\Provider;

use App\Booking\Enum\BookingStatus;
use App\Entity\Booking;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentSlot;
use App\Enum\Calendar\Weekday;
use App\Establishment\Enum\StatsPeriod;
use App\Establishment\Helper\CalendarHelper;
use App\Repository\BookingRepository;
use App\Repository\BookingStatsRepository;
use App\Repository\Establishment\EstablishmentSlotRepository;
use App\Shared\Helper\DurationHelper;
use App\Shared\Helper\MoneyHelper;
use DateTimeImmutable;

class ProStatsProvider
{
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

    public function build(
        CompanyEstablishment $establishment,
        int $period,
        bool $advanced,
        ?DateTimeImmutable $now = null,
    ): array {
        $now ??= new DateTimeImmutable();
        $period = StatsPeriod::tryFrom($period) ?? StatsPeriod::from(self::DEFAULT_PERIOD);
        $from = $now->modify(sprintf('-%d days', $period->value));
        $current = $this->slots->findBetween($establishment, $from, $now);
        $previousFrom = $from->modify(sprintf('-%d days', $period->value));
        $previous = $this->slots->findBetween($establishment, $previousFrom, $from);
        $data = [
            'period' => $period->value,
            'periods' => $this->periods($period),
            'kpis' => $this->kpis($establishment, $current, $previous, $from),
            'advanced' => $advanced,
            'weeks' => [],
            'services' => [],
            'heatmap' => null,
        ];

        if ($advanced) {
            $data['weeks'] = $this->weeks($establishment, $now);
            $data['services'] = $this->services($current);
            $data['heatmap'] = $this->heatmap($current);
        }

        return $data;
    }

    private function periods(StatsPeriod $selected): array
    {
        $periods = [];

        foreach (StatsPeriod::cases() as $period) {
            $periods[] = [
                'value' => $period->value,
                'label' => $period->label(),
                'selected' => $period === $selected
            ];
        }

        return $periods;
    }

    private function kpis(
        CompanyEstablishment $establishment,
        array $current,
        array $previous,
        DateTimeImmutable $from,
    ): array {
        return [
            $this->fillKpi($this->fillRate($current), $this->fillRate($previous)),
            $this->revenueKpi($this->earnedBookings($current)),
            $this->delayKpi($this->delays($current)),
            [
                'key' => 'customers',
                'label' => 'Nouveaux clients',
                'value' => (string) $this->newCustomers($establishment, $current, $from),
                'note' => 'venus par Kréno',
            ],
        ];
    }

    private function fillKpi(?int $rate, ?int $previousRate): array
    {
        $value = '—';
        $note = 'sur la période précédente';

        if ($rate !== null) {
            $value = sprintf('%d %%', $rate);
        }

        if ($rate !== null && $previousRate !== null) {
            $note = $this->pointsDelta($rate - $previousRate);
        }

        return [
            'key' => 'fill',
            'label' => 'Taux de remplissage',
            'value' => $value,
            'note' => $note
        ];
    }

    private function pointsDelta(int $delta): string
    {
        if ($delta >= 0) {
            return sprintf('+%d pts', $delta);
        }

        return sprintf('−%d pts', abs($delta));
    }

    private function revenueKpi(array $earned): array
    {
        $total = 0;

        foreach ($earned as $booking) {
            $total += $booking->getPrice();
        }

        $count = count($earned);
        $note = sprintf('sur %d créneau', $count);

        if ($count > 1) {
            $note = sprintf('sur %d créneaux', $count);
        }

        return [
            'key' => 'revenue',
            'label' => 'Chiffre récupéré',
            'value' => MoneyHelper::format($total),
            'note' => $note,
        ];
    }

    private function delayKpi(array $delays): array
    {
        $value = '—';

        if ($delays !== []) {
            $value = DurationHelper::format((int) round(array_sum($delays) / count($delays)));
        }

        return [
            'key' => 'delay',
            'label' => 'Délai moyen de réservation',
            'value' => $value,
            'note' => 'après publication',
        ];
    }

    private function earnedBookings(array $slots): array
    {
        $earned = [];

        foreach ($slots as $slot) {
            $booking = $this->booking($slot, self::EARNED_STATUSES);

            if ($booking !== null) {
                $earned[] = $booking;
            }
        }

        return $earned;
    }

    private function delays(array $slots): array
    {
        $delays = [];

        foreach ($slots as $slot) {
            $delay = $this->delay($slot);

            if ($delay !== null) {
                $delays[] = $delay;
            }
        }

        return $delays;
    }

    private function weeks(CompanyEstablishment $establishment, DateTimeImmutable $now): array
    {
        $start = CalendarHelper::mondayOf($now)->modify(sprintf('-%d weeks', self::WEEKS - 1));
        $weeks = $this->emptyWeeks($start);

        foreach ($this->slots->findBetween($establishment, $start, $now) as $slot) {
            $key = CalendarHelper::mondayOf($slot->getStartsAt() ?? $now)->format('Y-m-d');

            if (!isset($weeks[$key])) {
                continue;
            }

            ++$weeks[$key]['published'];

            if ($this->isBooked($slot)) {
                ++$weeks[$key]['booked'];
            }
        }

        $max = max([1, ...array_column($weeks, 'published')]);
        $rows = [];

        foreach ($weeks as $week) {
            $rows[] = $this->weekRow($week, $max);
        }

        return $rows;
    }

    private function emptyWeeks(DateTimeImmutable $start): array
    {
        $weeks = [];

        for ($i = 0; $i < self::WEEKS; ++$i) {
            $monday = $start->modify(sprintf('+%d weeks', $i));
            $label = 'S' . (int) $monday->format('W');
            $weeks[$monday->format('Y-m-d')] = ['label' => $label, 'published' => 0, 'booked' => 0];
        }

        return $weeks;
    }

    private function weekRow(array $week, int $max): array
    {
        $title = sprintf(
            '%s, %s',
            $this->countLabel($week['published'], 'publié', 'publiés'),
            $this->countLabel($week['booked'], 'réservé', 'réservés'),
        );

        return $week + [
                'publishedPercent' => (int) round($week['published'] / $max * 100),
                'bookedPercent' => (int) round($week['booked'] / $max * 100),
                'title' => $title,
            ];
    }

    private function services(array $slots): array
    {
        $counts = [];

        foreach ($slots as $slot) {
            $name = $slot->getService()?->getName();

            if ($name !== null && $this->isBooked($slot)) {
                $counts[$name] = ($counts[$name] ?? 0) + 1;
            }
        }

        arsort($counts);
        $counts = array_slice($counts, 0, self::TOP_SERVICES, true);
        $max = max([1, ...array_values($counts)]);
        $services = [];

        foreach ($counts as $name => $count) {
            $services[] = [
                'name' => $name,
                'count' => $count,
                'label' => $this->countLabel($count, 'réservation', 'réservations'),
                'percent' => (int) round($count / $max * 100),
            ];
        }

        return $services;
    }

    private function heatmap(array $slots): array
    {
        $cells = $this->delaysByCell($slots);
        $rows = [];
        $best = null;

        foreach (Weekday::cases() as $weekday) {
            $row = [
                'day' => $weekday->short(),
                'cells' => []
            ];

            foreach (self::HOURS as $hour) {
                $cell = $this->heatmapCell($weekday, $hour, $cells[$weekday->value][$hour] ?? []);
                $row['cells'][] = $cell;

                if ($this->isBetter($cell, $best)) {
                    $best = ['weekday' => $weekday] + $cell;
                }
            }

            $rows[] = $row;
        }

        $hours = [];

        foreach (self::HOURS as $hour) {
            $hours[] = $hour . 'h';
        }

        return [
            'hours' => $hours,
            'rows' => $rows,
            'tip' => $this->tip($best)
        ];
    }

    private function delaysByCell(array $slots): array
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

        return $cells;
    }

    private function heatmapCell(Weekday $weekday, int $hour, array $delays): array
    {
        $average = null;
        $title = sprintf('%s %dh · pas de donnée', $weekday->short(), $hour);

        if ($delays !== []) {
            $average = (int) round(array_sum($delays) / count($delays));
            $duration = DurationHelper::format($average);
            $title = sprintf('%s %dh · réservé en %s en moyenne', $weekday->short(), $hour, $duration);
        }

        return [
            'hour' => $hour,
            'level' => $this->level($average),
            'count' => count($delays),
            'title' => $title
        ];
    }

    private function isBetter(array $cell, ?array $best): bool
    {
        if ($cell['level'] === 0) {
            return false;
        }

        if ($best === null) {
            return true;
        }

        return [$cell['level'], $cell['count']] > [$best['level'], $best['count']];
    }

    private function tip(?array $best): ?string
    {
        if ($best === null) {
            return null;
        }

        return sprintf(
            'Vos créneaux partent le plus vite le %s vers %dh : publiez en priorité à ce moment-là.',
            mb_strtolower($best['weekday']->label()),
            $best['hour'],
        );
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

        $booked = 0;

        foreach ($slots as $slot) {
            if ($this->isBooked($slot)) {
                ++$booked;
            }
        }

        return (int) round($booked / count($slots) * 100);
    }

    private function delay(EstablishmentSlot $slot): ?int
    {
        $booking = $this->booking($slot, BookingRepository::KEPT_STATUSES);

        if ($booking?->getCreatedAt() === null || $slot->getCreatedAt() === null) {
            return null;
        }

        return max(0, intdiv($booking->getCreatedAt()->getTimestamp() - $slot->getCreatedAt()->getTimestamp(), 60));
    }

    private function isBooked(EstablishmentSlot $slot): bool
    {
        return $this->booking($slot, BookingRepository::KEPT_STATUSES) !== null;
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

    private function countLabel(int $count, string $singular, string $plural): string
    {
        if ($count > 1) {
            return sprintf('%d %s', $count, $plural);
        }

        return sprintf('%d %s', $count, $singular);
    }

    private function level(?int $minutes): int
    {
        if ($minutes === null) {
            return 0;
        }

        if ($minutes <= 15) {
            return 4;
        }

        if ($minutes <= 30) {
            return 3;
        }

        if ($minutes <= 60) {
            return 2;
        }

        return 1;
    }
}