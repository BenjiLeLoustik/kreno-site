<?php

namespace App\Faq\Provider;

use App\Faq\Enum\FaqAudience;
use App\Faq\Presenter\FaqPresenter;
use App\Repository\Faq\FaqCategoryRepository;
use NeoPHP\Package\Translation\TranslationManagerInterface;

class FaqProvider
{
    public function __construct(
        private FaqCategoryRepository $categories,
        private FaqPresenter $presenter,
        private TranslationManagerInterface $translation,
    ) {
    }

    public function sections(FaqAudience $audience, array $rules): array
    {
        $sections = [];

        foreach ($this->categories->findVisibleForAudience($audience, $this->translation->getLocale()) as $category) {
            $section = $this->presenter->publicSection($category, $rules);

            if ($section !== null) {
                $sections[] = $section;
            }
        }

        return $sections;
    }

    public function questions(FaqAudience $audience, array $rules): array
    {
        $questions = [];

        foreach ($this->categories->findVisibleForAudience($audience, $this->translation->getLocale()) as $category) {
            array_push($questions, ...$this->presenter->publicItems($category, $rules));
        }

        return $questions;
    }
}