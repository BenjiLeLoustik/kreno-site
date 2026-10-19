<?php

namespace App\User\Presenter;

use App\Entity\City;
use App\Entity\User\UserAlert;
use App\Shared\Helper\DateHelper;

class AlertPresenter
{
    public function present(UserAlert $alert): array
    {
        return [
            'id' => $alert->getId(),
            'label' => $alert->getLabel(),
            'city' => $this->city($alert->getCity()),
            'latitude' => $this->coordinate($alert->getLatitude()),
            'longitude' => $this->coordinate($alert->getLongitude()),
            'radiusKm' => $alert->getRadiusKm(),
            'startTime' => DateHelper::time($alert->getStartTime()),
            'endTime' => DateHelper::time($alert->getEndTime()),
            'maxPrice' => $alert->getMaxPrice(),
            'enabled' => $alert->isEnabled(),
            'categories' => $this->categories($alert),
        ];
    }

    public function presentAll(array $alerts): array
    {
        return array_map(fn (UserAlert $alert): array => $this->present($alert), $alerts);
    }

    private function city(?City $city): ?array
    {
        if ($city === null) {
            return null;
        }

        return [
            'name' => $city->getName(),
            'slug' => $city->getSlug()
        ];
    }

    private function coordinate(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        return (float) $value;
    }

    private function categories(UserAlert $alert): array
    {
        $categories = [];

        foreach ($alert->getCategories() as $category) {
            $categories[] = [
                'name' => $category->getName(),
                'slug' => $category->getSlug()
            ];
        }

        return $categories;
    }
}