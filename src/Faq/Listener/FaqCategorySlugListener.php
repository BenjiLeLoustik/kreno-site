<?php

namespace App\Faq\Listener;

use App\Entity\Faq\FaqCategoryTranslation;
use App\Repository\Faq\FaqCategoryTranslationRepository;
use App\Shared\Helper\StringHelper;
use NeoPHP\Component\Event\Attribute\AsListener;
use NeoPHP\Package\Orm\Event\PrePersistEvent;

class FaqCategorySlugListener
{
    public function __construct(
        private FaqCategoryTranslationRepository $translations,
    ) {
    }

    #[AsListener]
    public function onPrePersist(PrePersistEvent $event): void
    {
        $translation = $event->getEntity();

        if (!$translation instanceof FaqCategoryTranslation || ($translation->getSlug() ?? '') !== '') {
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