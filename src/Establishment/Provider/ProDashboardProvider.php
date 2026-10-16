<?php

namespace App\Establishment\Provider;

use App\Entity\Booking;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\User\User;
use App\Establishment\Enum\SlotStatus;
use App\Establishment\Helper\CalendarHelper;
use App\Establishment\Presenter\SlotPresenter;
use App\Repository\BookingRepository;
use App\Repository\Establishment\EstablishmentSlotRepository;
use App\Shared\Helper\DateHelper;
use App\Shared\Helper\MoneyHelper;
use App\User\Helper\UserNameHelper;
use DateTimeImmutable;

class ProDashboardProvider
{
    public const PERIOD_DAYS = 30;

    public const RECENT_BOOKINGS = 3;

    public function __construct(
        private EstablishmentSlotRepository $slots,
        private BookingRepository $bookings,
        private SlotPresenter $slotPresenter,
    ) {
    }

    public function build(CompanyEstablishment $establishment, User $user, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();

        return [
            'date' => CalendarHelper::longDate($now),
            'greeting' => sprintf('Bonjour %s', $user->getFirstName()),
            'stats' => $this->stats($establishment, $now),
            'today' => $this->today($establishment, $now),
            'recent' => $this->recent($establishment, $now),
        ];
    }

    private function today(CompanyEstablishment $establishment, DateTimeImmutable $now): array
    {
        $today = $now->setTime(0, 0);
        $rows = [];

        foreach ($this->slots->findBetween($establishment, $today, $today->modify('+1 day')) as $slot) {
            $rows[] = $this->slotPresenter->forDashboard($slot, $now);
        }

        return $rows;
    }

    private function recent(CompanyEstablishment $establishment, DateTimeImmutable $now): array
    {
        $rows = [];

        foreach ($this->bookings->findRecentForEstablishment($establishment, self::RECENT_BOOKINGS) as $booking) {
            $rows[] = $this->recentBooking($booking, $now);
        }

        return $rows;
    }

    private function recentBooking(Booking $booking, DateTimeImmutable $now): array
    {
        $slot = $booking->getSlot();

        return [
            'id' => $booking->getId(),
            'client' => $this->client($booking->getCustomer()),
            'when' => $this->relative($booking->getCreatedAt(), $now),
            'what' => sprintf('%s, %s', $slot?->getService()?->getName(), DateHelper::time($slot?->getStartsAt())),
            'price' => MoneyHelper::format($booking->getPrice()),
        ];
    }

    private function client(?User $customer): string
    {
        if ($customer === null) {
            return '—';
        }

        return UserNameHelper::shortName($customer);
    }

    private function relative(?DateTimeImmutable $date, DateTimeImmutable $now): ?string
    {
        if ($date === null) {
            return null;
        }

        return CalendarHelper::relative($date, $now);
    }

    private function stats(CompanyEstablishment $establishment, DateTimeImmutable $now): array
    {
        $since = $now->modify(sprintf('-%d days', self::PERIOD_DAYS));
        $previous = $since->modify(sprintf('-%d days', self::PERIOD_DAYS));
        $published = $this->slots->countCreatedSince($establishment, $since);
        $publishedBefore = $this->slots->countCreatedSince($establishment, $previous, $since);
        $booked = $this->slots->countCreatedSince($establishment, $since, null, SlotStatus::BOOKED);
        $revenue = $this->bookings->sumPriceForEstablishmentSince($establishment, $since);

        return [
            $this->publishedStat($published, $published - $publishedBefore),
            $this->stat('booked', 'Créneaux réservés', (string) $booked, $this->fillNote($published, $booked), 'up'),
            $this->stat('revenue', 'Chiffre récupéré', MoneyHelper::format($revenue), 'avec Kréno', 'neutral'),
            $this->stat(
                'customers',
                'Nouveaux clients',
                (string) $this->newCustomers($establishment, $since),
                'venus par Kréno',
                'neutral',
            ),
        ];
    }

    private function publishedStat(int $published, int $delta): array
    {
        $sign = '';
        $trend = 'down';

        if ($delta >= 0) {
            $sign = '+';
            $trend = 'up';
        }

        $note = sprintf('%s%d vs mois dernier', $sign, $delta);

        return $this->stat('published', 'Créneaux publiés', (string) $published, $note, $trend);
    }

    private function fillNote(int $published, int $booked): string
    {
        if ($published === 0) {
            return 'Aucun créneau publié';
        }

        return sprintf('%d %% de remplissage', round($booked / $published * 100));
    }

    private function newCustomers(CompanyEstablishment $establishment, DateTimeImmutable $since): int
    {
        $customers = [];

        foreach ($this->bookings->findForEstablishmentSince($establishment, $since) as $booking) {
            $customer = $booking->getCustomer();

            if ($customer === null || isset($customers[$customer->getId()])) {
                continue;
            }

            $customers[$customer->getId()] = !$this->bookings->customerBookedBefore($establishment, $customer, $since);
        }

        return count(array_filter($customers));
    }

    private function stat(string $key, string $label, string $value, string $note, string $trend): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'note' => $note,
            'trend' => $trend
        ];
    }
}