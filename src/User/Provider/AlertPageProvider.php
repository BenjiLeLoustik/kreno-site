<?php

namespace App\User\Provider;

use App\Entity\User\User;
use App\Entity\User\UserAlert;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use App\Repository\User\UserAlertRepository;
use App\User\Exception\AlertNotFoundException;
use App\User\Presenter\AlertPresenter;
use App\User\Service\AlertService;

class AlertPageProvider
{
    public function __construct(
        private UserAlertRepository $alerts,
        private AlertPresenter $presenter,
        private CategoryRepository $categories,
        private CityRepository $cities,
    ) {
    }

    public function index(User $user): array
    {
        $alerts = $this->presenter->presentAll($this->alerts->findByUser($user));

        return [
            'alerts' => $alerts,
            'canCreate' => count($alerts) < AlertService::MAX_ALERTS,
            'maxAlerts' => AlertService::MAX_ALERTS,
            'categories' => $this->categories(),
            'cities' => $this->cities(),
        ];
    }

    public function owned(int $id, User $user): UserAlert
    {
        $alert = $this->alerts->find($id);

        if (!$alert instanceof UserAlert || $alert->getUser() !== $user) {
            throw new AlertNotFoundException();
        }

        return $alert;
    }

    private function categories(): array
    {
        $categories = [];

        foreach ($this->categories->findVisible() as $category) {
            $categories[] = [
                'name' => $category->getName(),
                'slug' => $category->getSlug()
            ];
        }

        return $categories;
    }

    private function cities(): array
    {
        $cities = [];

        foreach ($this->cities->findOpen() as $city) {
            $cities[] = [
                'name' => $city->getName(),
                'slug' => $city->getSlug()
            ];
        }

        return $cities;
    }
}