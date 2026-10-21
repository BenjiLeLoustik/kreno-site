<?php

namespace App\Shared\Service;

use App\Entity\Category;
use App\Entity\CategoryTranslation;
use App\Repository\CategoryTranslationRepository;
use NeoPHP\Package\Translation\TranslationManagerInterface;

class TranslatableResolver
{
    public function __construct(
        private TranslationManagerInterface $translation,
        private CategoryTranslationRepository $categoryTranslations,
    ) {
    }

    public function category(Category $category): ?CategoryTranslation
    {
        $locale = $this->translation->getLocale();
        $translation = $this->categoryTranslations->findOneByCategoryAndLocale($category, $locale);

        if ($translation !== null) {
            return $translation;
        }

        foreach ($this->translation->getFallbackLocales($locale) as $fallback) {
            $translation = $this->categoryTranslations->findOneByCategoryAndLocale($category, $fallback);

            if ($translation !== null) {
                return $translation;
            }
        }

        foreach ($category->getTranslations() as $available) {
            return $available;
        }

        return null;
    }
}