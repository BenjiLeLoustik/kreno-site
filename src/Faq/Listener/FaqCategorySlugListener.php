<?php

namespace App\Faq\Listener;

use App\Entity\Faq\FaqCategory;
use App\Repository\Faq\FaqCategoryRepository;
use App\Shared\Helper\StringHelper;
use NeoPHP\Component\Event\Attribute\AsListener;
use NeoPHP\Package\Orm\Event\PrePersistEvent;

class FaqCategorySlugListener
{
    public function __construct(
        private FaqCategoryRepository $categories,
    ) {
    }

    #[AsListener]
    public function onPrePersist(PrePersistEvent $event): void
    {
        $category = $event->getEntity();

        if (!$category instanceof FaqCategory || ($category->getSlug() ?? '') !== '') {
            return;
        }

        $category->setSlug(StringHelper::uniqueSlug((string) $category->getName(), $this->slugExists(...)));
    }

    private function slugExists(string $slug): bool
    {
        return $this->categories->slugExists($slug);
    }
}