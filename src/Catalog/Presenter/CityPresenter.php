<?php

namespace App\Catalog\Presenter;

use App\Entity\City;
use App\Shared\Helper\DateHelper;

class CityPresenter
{
    public function summary(City $city): array
    {
        return [
            'id' => $city->getId(),
            'name' => $city->getName(),
            'slug' => $city->getSlug(),
            'status' => $city->getStatus()->value,
            'statusLabel' => $city->getStatus()->label(),
        ];
    }

    public function detail(City $city): array
    {
        return $this->summary($city) + [
                'departmentCode' => $city->getDepartmentCode(),
                'latitude' => $city->getLatitude() !== null ? (float) $city->getLatitude() : null,
                'longitude' => $city->getLongitude() !== null ? (float) $city->getLongitude() : null,
                'radiusKm' => $city->getRadiusKm(),
                'position' => $city->getPosition(),
                'openedAt' => DateHelper::iso($city->getOpenedAt()),
            ];
    }

    public function summaries(array $cities): array
    {
        return array_map(fn (City $city): array => $this->summary($city), $cities);
    }
}