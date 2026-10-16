<?php

namespace App\User\Provider;

use App\Booking\Provider\CustomerDashboardProvider;
use App\Entity\User\User;
use App\User\Presenter\AlertPresenter;
use App\User\Presenter\UserPresenter;
use App\Website\Provider\WebsiteProvider;

class AccountPageProvider
{
    public function __construct(
        private UserPresenter $users,
        private AlertPresenter $alerts,
        private CustomerDashboardProvider $dashboard,
        private WebsiteProvider $website,
    ) {
    }

    public function index(User $user): array
    {
        return [
            'user' => $this->users->profile($user),
            'dashboard' => $this->dashboard->build($user),
            'alerts' => $this->alerts->presentAll($user->getAlerts()->toArray()),
            'rules' => $this->customerRules(),
        ];
    }

    public function settings(User $user): array
    {
        return [
            'profile' => $this->users->profile($user),
        ];
    }

    private function customerRules(): array
    {
        $rule = $this->website->getRule();

        return [
            'freeCancellationMinutes' => $rule->getFreeCancellationMinutes(),
            'maxUpcomingBookings' => $rule->getMaxUpcomingBookingsPerUser(),
            'toleratedNoShows' => $rule->getToleratedNoShows(),
            'noShowWindowDays' => $rule->getNoShowWindowDays(),
            'suspensionDays' => $rule->getSuspensionDays(),
        ];
    }
}