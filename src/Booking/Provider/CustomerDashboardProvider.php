<?php

namespace App\Booking\Provider;

use App\Booking\Enum\BookingStatus;
use App\Booking\Presenter\BookingPresenter;
use App\Entity\Booking;
use App\Entity\User\User;
use App\Entity\User\UserAlert;
use App\Establishment\Helper\CalendarHelper;
use App\Establishment\Presenter\SlotPresenter;
use App\Establishment\Service\AlertMatchService;
use App\Repository\BookingRepository;
use App\Repository\User\UserNotificationRepository;
use App\Shared\Helper\MoneyHelper;
use App\User\Presenter\NotificationPresenter;
use App\Website\Provider\WebsiteProvider;
use DateTimeImmutable;

class CustomerDashboardProvider
{
    public function __construct(
        private BookingRepository $bookings,
        private UserNotificationRepository $notifications,
        private WebsiteProvider $website,
        private AlertMatchService $alerts,
        private BookingPresenter $bookingPresenter,
        private SlotPresenter $slotPresenter,
        private NotificationPresenter $notificationPresenter,
    ) {
    }

    public function build(User $user, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $rule = $this->website->getRule();
        $upcoming = $this->bookings->findUpcomingForCustomer($user, $now);
        $savings = $this->savings($user);
        $alert = null;

        foreach ($user->getAlerts() as $candidate) {
            if ($candidate instanceof UserAlert && $candidate->isEnabled()) {
                $alert = $candidate;
                break;
            }
        }

        return [
            'date' => CalendarHelper::longDate($now),
            'greeting' => sprintf('Bonjour %s', $user->getFirstName()),
            'since' => $user->getCreatedAt() !== null ? 'depuis ' . CalendarHelper::monthYear($user->getCreatedAt()) : null,
            'savedTotal' => MoneyHelper::format($savings['saved']),
            'next' => $upcoming !== [] ? $this->bookingPresenter->forCustomer($upcoming[0], $now) : null,
            'stats' => [
                ['key' => 'bookings', 'label' => 'Réservations', 'value' => (string) $this->bookings->countKeptForCustomer($user), 'note' => sprintf('dont %d à venir', count($upcoming))],
                ['key' => 'saved', 'label' => 'Économisé', 'value' => MoneyHelper::format($savings['saved']), 'note' => $savings['discount'] !== null ? sprintf('-%d %% en moyenne', $savings['discount']) : 'Pas encore de réservation'],
                ['key' => 'noShows', 'label' => 'Absences', 'value' => (string) $this->bookings->countNoShowsSince($user, $now->modify(sprintf('-%d days', $rule->getNoShowWindowDays()))), 'note' => sprintf('/ %d tolérées sur les %d derniers jours', $rule->getToleratedNoShows(), $rule->getNoShowWindowDays())],
            ],
            'alert' => $alert !== null ? ['id' => $alert->getId(), 'summary' => $this->alerts->describe($alert)] : null,
            'picks' => $this->slotPresenter->cards($this->alerts->picksFor($user, $now, 3), $now),
            'notifications' => $this->notificationPresenter->presentAll($this->notifications->findLatestForUser($user, 3)),
            'unreadNotifications' => $this->notifications->countUnread($user),
        ];
    }

    private function savings(User $user): array
    {
        $saved = 0;
        $discounts = [];

        foreach ($this->bookings->findForCustomer($user, [BookingStatus::CONFIRMED, BookingStatus::HONORED], null, null, 500) as $booking) {
            if ($booking instanceof Booking && $booking->getSlot() !== null) {
                $saved += max(0, $booking->getSlot()->getOriginalPrice() - $booking->getPrice());
                $discounts[] = $booking->getSlot()->getDiscount();
            }
        }

        return ['saved' => $saved, 'discount' => $discounts !== [] ? (int) round(array_sum($discounts) / count($discounts)) : null];
    }
}