<?php

namespace App\Company\Listener;

use App\Entity\Company\CompanyEstablishment;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Shared\Helper\StringHelper;
use NeoPHP\Component\Event\Attribute\AsListener;
use NeoPHP\Package\Orm\Event\PrePersistEvent;

class EstablishmentSlugListener
{
    public function __construct(
        private CompanyEstablishmentRepository $establishments
    ) {
    }

    #[AsListener]
    public function onPrePersist(PrePersistEvent $event): void
    {
        $establishment = $event->getEntity();

        if (!$establishment instanceof CompanyEstablishment || ($establishment->getSlug() !== null && $establishment->getSlug() !== '') || $establishment->getCity() === null) {
            return;
        }

        $city = $establishment->getCity();
        $establishment->setSlug(
            StringHelper::uniqueSlug(
                (string) $establishment->getName(),
                fn (string $slug): bool => $this->establishments->slugExistsInCity($city, $slug)
            )
        );
    }
}