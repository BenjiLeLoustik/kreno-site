<?php

namespace App\Catalog\Listener;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Shared\Helper\StringHelper;
use NeoPHP\Component\Event\Attribute\AsListener;
use NeoPHP\Package\Orm\Event\PrePersistEvent;

class CategorySlugListener
{
    public function __construct(
        private CategoryRepository $categories
    ) {
    }

    #[AsListener]
    public function onPrePersist(PrePersistEvent $event): void
    {
        $category = $event->getEntity();

        if (!$category instanceof Category || ($category->getSlug() !== null && $category->getSlug() !== '')) {
            return;
        }

        $category->setSlug(
            StringHelper::uniqueSlug(
                (string) $category->getName(),
                fn (string $slug): bool => $this->categories->slugExists($slug)
            )
        );
    }
}