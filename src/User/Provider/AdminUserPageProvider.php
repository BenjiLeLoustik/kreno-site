<?php

namespace App\User\Provider;

use App\Booking\Enum\BookingStatus;
use App\Entity\User\User;
use App\Repository\BookingRepository;
use App\Repository\User\UserRepository;
use App\Shared\Mapper\AdminQueryMapper;
use App\User\Enum\UserStatus;
use App\User\Exception\UserNotFoundException;
use App\User\Presenter\UserPresenter;
use App\Website\Provider\WebsiteProvider;
use DateTimeImmutable;

class AdminUserPageProvider
{
    public const PER_PAGE = 50;

    public function __construct(
        private UserRepository $users,
        private BookingRepository $bookings,
        private WebsiteProvider $website,
        private UserPresenter $presenter,
    ) {
    }

    public function index(int $page, ?UserStatus $status, string $search, ?User $selected = null): array
    {
        $users = $this->users->search(
            AdminQueryMapper::nullable($search),
            $status,
            self::PER_PAGE + 1,
            ($page - 1) * self::PER_PAGE,
        );
        $visible = array_slice($users, 0, self::PER_PAGE);

        return [
            'filters' => ['q' => $search, 'status' => $status?->value, 'page' => $page],
            'hasNext' => count($users) > self::PER_PAGE,
            'statuses' => $this->statuses(),
            'users' => $this->rows($visible),
            'selected' => $this->detail($selected ?? $visible[0] ?? null),
            'suspensionDays' => $this->website->getRule()->getSuspensionDays(),
        ];
    }

    public function user(int $id): User
    {
        $user = $this->users->find($id);

        if (!$user instanceof User) {
            throw new UserNotFoundException();
        }

        return $user;
    }

    private function statuses(): array
    {
        $statuses = [];

        foreach (UserStatus::cases() as $status) {
            $statuses[] = ['value' => $status->value, 'label' => $status->label()];
        }

        return $statuses;
    }

    private function rows(array $users): array
    {
        $rows = [];

        foreach ($users as $user) {
            $rows[] = $this->presenter->admin($user);
        }

        return $rows;
    }

    private function detail(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return $this->presenter->admin($user, $this->stats($user));
    }

    private function stats(User $user): array
    {
        $rule = $this->website->getRule();
        $since = new DateTimeImmutable(sprintf('-%d days', $rule->getNoShowWindowDays()));

        return [
            'bookings' => $this->bookings->countByCustomer($user),
            'honored' => $this->bookings->countByCustomer($user, BookingStatus::HONORED),
            'cancelled' => $this->bookings->countByCustomer($user, BookingStatus::CANCELLED_BY_CUSTOMER),
            'noShows' => $this->bookings->countByCustomer($user, BookingStatus::NO_SHOW),
            'recentNoShows' => $this->bookings->countNoShowsSince($user, $since),
            'toleratedNoShows' => $rule->getToleratedNoShows(),
            'noShowWindowDays' => $rule->getNoShowWindowDays(),
        ];
    }
}