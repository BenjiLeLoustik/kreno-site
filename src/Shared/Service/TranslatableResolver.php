<?php

namespace App\Shared\Service;

use App\Entity\AbstractEntityTranslated;
use App\Entity\Category;
use App\Entity\CategoryTranslation;
use App\Entity\Faq\FaqCategory;
use App\Entity\Faq\FaqCategoryTranslation;
use App\Entity\Faq\FaqQuestion;
use App\Entity\Faq\FaqQuestionTranslation;
use App\Repository\CategoryTranslationRepository;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
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

    public function faqCategory(FaqCategory $category): ?FaqCategoryTranslation
    {
        return $this->resolve($category->getTranslations());
    }

    public function faqQuestion(FaqQuestion $question): ?FaqQuestionTranslation
    {
        return $this->resolve($question->getTranslations());
    }

    public function exact(CollectionInterface $translations, string $locale): ?AbstractEntityTranslated
    {
        foreach ($translations as $translation) {
            if ($translation->getLocale() === $locale) {
                return $translation;
            }
        }

        return null;
    }

    private function resolve(CollectionInterface $translations): ?AbstractEntityTranslated
    {
        $locale = $this->translation->getLocale();

        foreach ([$locale, ...$this->translation->getFallbackLocales($locale)] as $candidate) {
            $translation = $this->exact($translations, $candidate);

            if ($translation !== null) {
                return $translation;
            }
        }

        foreach ($translations as $available) {
            return $available;
        }

        return null;
    }
}