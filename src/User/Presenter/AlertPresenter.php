<?php

namespace App\User\Presenter;

use App\Entity\Category;
use App\Entity\User\UserAlert;
use App\Shared\Helper\DateHelper;

class AlertPresenter
{
    public function present(UserAlert $alert): array
    {
        return [
            'id' => $alert->getId(),
            'label' => $alert->getLabel(),
            'city' => $alert->getCity() === null ? null : ['name' => $alert->getCity()->getName(), 'slug' => $alert->getCity()->getSlug()],
            'latitude' => $alert->getLatitude() !== null ? (float) $alert->getLatitude() : null,
            'longitude' => $alert->getLongitude() !== null ? (float) $alert->getLongitude() : null,
            'radiusKm' => $alert->getRadiusKm(),
            'startTime' => DateHelper::time($alert->getStartTime()),
            'endTime' => DateHelper::time($alert->getEndTime()),
            'maxPrice' => $alert->getMaxPrice(),
            'enabled' => $alert->isEnabled(),
            'categories' => array_map(static fn (Category $category): array => ['name' => $category->getName(), 'slug' => $category->getSlug()], $alert->getCategories()->toArray()),
        ];
    }

    public function presentAll(array $alerts): array
    {
        return array_map(fn (UserAlert $alert): array => $this->present($alert), $alerts);
    }
}