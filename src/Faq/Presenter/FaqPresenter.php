<?php

namespace App\Faq\Presenter;

use App\Entity\Faq\FaqCategory;
use App\Entity\Faq\FaqQuestion;
use App\Faq\Enum\FaqPlaceholder;
use App\Shared\Service\TranslatableResolver;

class FaqPresenter
{
    public function __construct(
        private TranslatableResolver $resolver,
    ) {
    }

    public function adminCategory(FaqCategory $category): array
    {
        $translation = $this->resolver->faqCategory($category);
        $questions = [];

        foreach ($category->getQuestions() as $question) {
            $questions[] = $this->adminQuestion($question);
        }

        return [
            'slug' => $translation?->getSlug() ?? (string) $category->getId(),
            'name' => $translation?->getName(),
            'audience' => $category->getAudience()->value,
            'visible' => $category->isVisible(),
            'questionsCount' => count($questions),
            'questionIds' => array_column($questions, 'id'),
            'questions' => $questions,
        ];
    }

    public function publicSection(FaqCategory $category, array $rules): ?array
    {
        $items = $this->publicItems($category, $rules);

        if ($items === []) {
            return null;
        }

        $translation = $this->resolver->faqCategory($category);

        return [
            'key' => $translation?->getSlug(),
            'label' => $translation?->getName(),
            'items' => $items
        ];
    }

    public function publicItems(FaqCategory $category, array $rules): array
    {
        $items = [];

        foreach ($category->getQuestions() as $question) {
            $translation = $this->resolver->faqQuestion($question);

            if ($question->isVisible() && $translation !== null) {
                $items[] = [
                    'question' => $translation->getName(),
                    'answer' => FaqPlaceholder::replace((string) $translation->getAnswer(), $rules),
                ];
            }
        }

        return $items;
    }

    private function adminQuestion(FaqQuestion $question): array
    {
        $translation = $this->resolver->faqQuestion($question);

        return [
            'id' => $question->getId(),
            'question' => $translation?->getName(),
            'answer' => $translation?->getAnswer(),
            'visible' => $question->isVisible(),
        ];
    }
}