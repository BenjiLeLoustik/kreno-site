<?php

namespace App\Catalog\Listener;

use App\Entity\City;
use App\Repository\CityRepository;
use App\Shared\Helper\StringHelper;
use NeoPHP\Component\Event\Attribute\AsListener;
use NeoPHP\Package\Orm\Event\PrePersistEvent;

class CitySlugListener
{
    public function __construct(
        private CityRepository $cities
    ) {
    }

    #[AsListener]
    public function onPrePersist(PrePersistEvent $event): void
    {
        $city = $event->getEntity();

        if (!$city instanceof City || ($city->getSlug() !== null && $city->getSlug() !== '')) {
            return;
        }

        $city->setSlug(StringHelper::uniqueSlug((string) $city->getName(), fn (string $slug): bool => $this->cities->slugExists($slug)));
    }
}