<?php

namespace App\Faq\Provider;

use App\Entity\Faq\FaqCategory;
use App\Entity\Faq\FaqQuestion;
use App\Faq\Enum\FaqAudience;
use App\Faq\Enum\FaqPlaceholder;
use App\Faq\Exception\FaqCategoryNotFoundException;
use App\Faq\Exception\FaqQuestionNotFoundException;
use App\Faq\Presenter\FaqPresenter;
use App\Repository\Faq\FaqCategoryRepository;
use App\Repository\Faq\FaqQuestionRepository;
use App\Shared\Service\TranslatableResolver;
use NeoPHP\Package\Translation\TranslationManagerInterface;

class AdminFaqPageProvider
{
    public function __construct(
        private FaqCategoryRepository $categories,
        private FaqQuestionRepository $questions,
        private FaqPresenter $presenter,
        private TranslatableResolver $resolver,
        private TranslationManagerInterface $translation,
    ) {
    }

    public function index(): array
    {
        return [
            'audiences' => $this->audiences(),
            'audienceChoices' => FaqAudience::choices(),
            'placeholders' => FaqPlaceholder::choices(),
        ];
    }

    public function categoryShow(string $slug, ?string $locale = null): array
    {
        $category = $this->category($slug);
        $locale = $this->locale($locale);
        $translation = $this->resolver->exact($category->getTranslations(), $locale);

        return [
            'category' => $this->presenter->adminCategory($category),
            'translation' => $translation === null ? null : [
                'name' => $translation->getName(),
                'slug' => $translation->getSlug(),
            ],
            'locale' => $locale,
            'locales' => $this->translation->getLocales(),
            'audienceChoices' => FaqAudience::choices(),
        ];
    }

    public function questionShow(int $id, ?string $locale = null): array
    {
        $question = $this->question($id);
        $category = $question->getCategory();
        $locale = $this->locale($locale);
        $translation = $this->resolver->exact($question->getTranslations(), $locale);

        return [
            'question' => [
                'id' => $question->getId(),
                'visible' => $question->isVisible(),
            ],
            'translation' => $translation === null ? null : [
                'question' => $translation->getName(),
                'answer' => $translation->getAnswer(),
            ],
            'locale' => $locale,
            'locales' => $this->translation->getLocales(),
            'category' => [
                'slug' => $this->slug($category),
                'name' => $this->name($category),
            ],
        ];
    }

    public function category(string $slug): FaqCategory
    {
        $category = $this->categories->findOneByAnySlugOrId($slug);

        if ($category === null) {
            throw new FaqCategoryNotFoundException($slug);
        }

        return $category;
    }

    public function question(int $id): FaqQuestion
    {
        $question = $this->questions->findOneById($id);

        if ($question === null) {
            throw new FaqQuestionNotFoundException($id);
        }

        return $question;
    }

    public function name(?FaqCategory $category): ?string
    {
        if ($category === null) {
            return null;
        }

        return $this->resolver->faqCategory($category)?->getName();
    }

    public function slug(?FaqCategory $category): ?string
    {
        if ($category === null) {
            return null;
        }

        return $this->resolver->faqCategory($category)?->getSlug() ?? (string) $category->getId();
    }

    public function locale(?string $locale): string
    {
        return $this->translation->matchLocale($locale ?? $this->translation->getLocale())
            ?? $this->translation->getLocale();
    }

    private function audiences(): array
    {
        $audiences = [];

        foreach (FaqAudience::cases() as $audience) {
            $categories = $this->rows($this->categories->findForAudience($audience));
            $audiences[] = [
                'value' => $audience->value,
                'label' => $audience->label(),
                'page' => $audience->page(),
                'slugs' => array_column($categories, 'slug'),
                'categories' => $categories,
            ];
        }

        return $audiences;
    }

    private function rows(array $categories): array
    {
        $rows = [];

        foreach ($categories as $category) {
            $rows[] = $this->presenter->adminCategory($category);
        }

        return $rows;
    }
}