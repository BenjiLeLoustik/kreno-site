<?php

namespace App\Establishment\Provider;

use App\Entity\Booking;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentSlot;
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

    public function __construct(
        private EstablishmentSlotRepository $slots,
        private BookingRepository $bookings,
        private SlotPresenter $slotPresenter,
    ) {
    }

    public function build(CompanyEstablishment $establishment, User $user, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $today = $now->setTime(0, 0);

        return [
            'date' => CalendarHelper::longDate($now),
            'greeting' => sprintf('Bonjour %s', $user->getFirstName()),
            'stats' => $this->stats($establishment, $now),
            'today' => array_map(fn (EstablishmentSlot $slot): array => $this->slotPresenter->forDashboard($slot, $now), $this->slots->findBetween($establishment, $today, $today->modify('+1 day'))),
            'recent' => array_map(static fn (Booking $booking): array => [
                'id' => $booking->getId(),
                'client' => $booking->getCustomer() !== null ? UserNameHelper::shortName($booking->getCustomer()) : '—',
                'when' => $booking->getCreatedAt() !== null ? CalendarHelper::relative($booking->getCreatedAt(), $now) : null,
                'what' => sprintf('%s, %s', $booking->getSlot()?->getService()?->getName(), DateHelper::time($booking->getSlot()?->getStartsAt())),
                'price' => MoneyHelper::format($booking->getPrice()),
            ], $this->bookings->findRecentForEstablishment($establishment, 3)),
        ];
    }

    private function stats(CompanyEstablishment $establishment, DateTimeImmutable $now): array
    {
        $since = $now->modify(sprintf('-%d days', self::PERIOD_DAYS));
        $previous = $since->modify(sprintf('-%d days', self::PERIOD_DAYS));
        $published = $this->slots->countCreatedSince($establishment, $since);
        $publishedBefore = $this->slots->countCreatedSince($establishment, $previous, $since);
        $booked = $this->slots->countCreatedSince($establishment, $since, null, SlotStatus::BOOKED);
        $revenue = $this->bookings->sumPriceForEstablishmentSince($establishment, $since);
        $customers = [];

        foreach ($this->bookings->findForEstablishmentSince($establishment, $since) as $booking) {
            $customer = $booking->getCustomer();

            if ($customer !== null && !isset($customers[$customer->getId()])) {
                $customers[$customer->getId()] = !$this->bookings->customerBookedBefore($establishment, $customer, $since);
            }
        }

        $delta = $published - $publishedBefore;

        return [
            ['key' => 'published', 'label' => 'Créneaux publiés', 'value' => (string) $published, 'note' => sprintf('%s%d vs mois dernier', $delta >= 0 ? '+' : '', $delta), 'trend' => $delta >= 0 ? 'up' : 'down'],
            ['key' => 'booked', 'label' => 'Créneaux réservés', 'value' => (string) $booked, 'note' => $published > 0 ? sprintf('%d %% de remplissage', round($booked / $published * 100)) : 'Aucun créneau publié', 'trend' => 'up'],
            ['key' => 'revenue', 'label' => 'Chiffre récupéré', 'value' => MoneyHelper::format($revenue), 'note' => 'avec Kréno', 'trend' => 'neutral'],
            ['key' => 'customers', 'label' => 'Nouveaux clients', 'value' => (string) count(array_filter($customers)), 'note' => 'venus par Kréno', 'trend' => 'neutral'],
        ];
    }
}