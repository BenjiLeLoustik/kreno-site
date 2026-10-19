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
use App\Shared\Helper\Translation;
use App\User\Presenter\NotificationPresenter;
use App\Website\Provider\WebsiteProvider;
use DateTimeImmutable;

class CustomerDashboardProvider
{
    public const SAVINGS_LIMIT = 500;

    public const PICKS = 3;

    public const NOTIFICATIONS = 3;

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
        $upcoming = $this->bookings->findUpcomingForCustomer($user, $now);
        $savings = $this->savings($user);
        $notifications = $this->notifications->findLatestForUser($user, self::NOTIFICATIONS);

        return [
            'date' => CalendarHelper::longDate($now),
            'greeting' => sprintf(Translation::translate('Bonjour %s'), $user->getFirstName()),
            'since' => $this->since($user),
            'savedTotal' => MoneyHelper::format($savings['saved']),
            'next' => $this->next($upcoming, $now),
            'stats' => [
                $this->bookingsStat($user, count($upcoming)),
                $this->savedStat($savings),
                $this->noShowsStat($user, $now),
            ],
            'alert' => $this->alert($user),
            'picks' => $this->slotPresenter->cards($this->alerts->picksFor($user, $now, self::PICKS), $now),
            'notifications' => $this->notificationPresenter->presentAll($notifications),
            'unreadNotifications' => $this->notifications->countUnread($user),
        ];
    }

    private function since(User $user): ?string
    {
        $createdAt = $user->getCreatedAt();

        if ($createdAt === null) {
            return null;
        }

        return Translation::translate('depuis {year}', [
            'year' => CalendarHelper::monthYear($createdAt)
        ]);
    }

    private function next(array $upcoming, DateTimeImmutable $now): ?array
    {
        if ($upcoming === []) {
            return null;
        }

        return $this->bookingPresenter->forCustomer($upcoming[0], $now);
    }

    private function bookingsStat(User $user, int $upcoming): array
    {
        $count = (string) $this->bookings->countKeptForCustomer($user);

        return $this->stat(
            'bookings',
            Translation::translate('Réservations'),
            $count,
            sprintf(Translation::translate('dont %d à venir'), $upcoming)
        );
    }

    private function savedStat(array $savings): array
    {
        $note = Translation::translate('Pas encore de réservation');

        if ($savings['discount'] !== null) {
            $note = sprintf(Translation::translate('-%d %% en moyenne'), $savings['discount']);
        }

        return $this->stat(
            'saved',
            Translation::translate('Économisé'),
            MoneyHelper::format($savings['saved']),
            $note
        );
    }

    private function noShowsStat(User $user, DateTimeImmutable $now): array
    {
        $rule = $this->website->getRule();
        $windowDays = $rule->getNoShowWindowDays();
        $count = $this->bookings->countNoShowsSince($user, $now->modify(sprintf('-%d days', $windowDays)));
        $note = sprintf(
            Translation::translate('/ %d tolérées sur les %d derniers jours'),
            $rule->getToleratedNoShows(),
            $windowDays
        );

        return $this->stat(
            'noShows',
            Translation::translate('Absences'),
            (string) $count,
            $note
        );
    }

    private function stat(string $key, string $label, string $value, string $note): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'note' => $note
        ];
    }

    private function alert(User $user): ?array
    {
        foreach ($user->getAlerts() as $alert) {
            if ($alert instanceof UserAlert && $alert->isEnabled()) {
                return [
                    'id' => $alert->getId(),
                    'summary' => $this->alerts->describe($alert)
                ];
            }
        }

        return null;
    }

    private function savings(User $user): array
    {
        $saved = 0;
        $discounts = [];
        $statuses = [BookingStatus::CONFIRMED, BookingStatus::HONORED];

        foreach ($this->bookings->findForCustomer($user, $statuses, null, null, self::SAVINGS_LIMIT) as $booking) {
            $slot = $booking instanceof Booking ? $booking->getSlot() : null;

            if ($slot === null) {
                continue;
            }

            $saved += max(0, $slot->getOriginalPrice() - $booking->getPrice());
            $discounts[] = $slot->getDiscount();
        }

        return [
            'saved' => $saved,
            'discount' => $this->averageDiscount($discounts)
        ];
    }

    private function averageDiscount(array $discounts): ?int
    {
        if ($discounts === []) {
            return null;
        }

        return (int) round(array_sum($discounts) / count($discounts));
    }
}