<?php

namespace App\Faq\Presenter;

use App\Entity\Faq\FaqCategory;
use App\Entity\Faq\FaqQuestion;
use App\Faq\Enum\FaqPlaceholder;

class FaqPresenter
{
    public function adminCategory(FaqCategory $category): array
    {
        $questions = [];

        foreach ($category->getQuestions() as $question) {
            $questions[] = $this->adminQuestion($question);
        }

        return [
            'slug' => $category->getSlug(),
            'name' => $category->getName(),
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

        return ['key' => $category->getSlug(), 'label' => $category->getName(), 'items' => $items];
    }

    public function publicItems(FaqCategory $category, array $rules): array
    {
        $items = [];

        foreach ($category->getQuestions() as $question) {
            if ($question->isVisible()) {
                $items[] = [
                    'question' => $question->getQuestion(),
                    'answer' => FaqPlaceholder::replace((string) $question->getAnswer(), $rules),
                ];
            }
        }

        return $items;
    }

    private function adminQuestion(FaqQuestion $question): array
    {
        return [
            'id' => $question->getId(),
            'question' => $question->getQuestion(),
            'answer' => $question->getAnswer(),
            'visible' => $question->isVisible(),
        ];
    }
}