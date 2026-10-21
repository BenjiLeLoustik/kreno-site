<?php

namespace App\Catalog\Listener;

use App\Entity\CategoryTranslation;
use App\Repository\CategoryTranslationRepository;
use App\Shared\Helper\StringHelper;
use NeoPHP\Component\Event\Attribute\AsListener;
use NeoPHP\Package\Orm\Event\PrePersistEvent;

class CategorySlugListener
{
    public function __construct(
        private CategoryTranslationRepository $translations
    ) {
    }

    #[AsListener]
    public function onPrePersist(PrePersistEvent $event): void
    {
        $translation = $event->getEntity();

        if (!$translation instanceof CategoryTranslation) {
            return;
        }

        if ($translation->getSlug() !== null && $translation->getSlug() !== '') {
            return;
        }

        $translation->setSlug(
            StringHelper::uniqueSlug(
                (string) $translation->getName(),
                fn (string $slug): bool => $this->translations->slugExists(
                    $slug,
                    $translation->getLocale(),
                    $translation->getId()
                )
            )
        );
    }
}