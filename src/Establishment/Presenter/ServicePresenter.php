<?php

namespace App\Establishment\Presenter;

use App\Entity\Establishment\EstablishmentService;
use App\Shared\Helper\DurationHelper;
use App\Shared\Helper\MoneyHelper;

class ServicePresenter
{
    public function summary(EstablishmentService $service): array
    {
        return [
            'id' => $service->getId(),
            'name' => $service->getName(),
            'description' => $service->getDescription(),
            'price' => $service->getPrice(),
            'priceLabel' => MoneyHelper::format($service->getPrice()),
            'duration' => $service->getDuration(),
            'durationLabel' => DurationHelper::format($service->getDuration()),
            'defaultDiscount' => $service->getDefaultDiscount(),
            'discountLabel' => $service->getDefaultDiscount() !== null ? sprintf('-%d %%', $service->getDefaultDiscount()) : null,
            'active' => $service->isActive(),
            'position' => $service->getPosition(),
        ];
    }

    public function summaries(array $services): array
    {
        return array_values(array_map($this->summary(...), $services));
    }
}