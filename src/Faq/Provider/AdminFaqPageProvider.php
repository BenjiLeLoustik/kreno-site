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

class AdminFaqPageProvider
{
    public function __construct(
        private FaqCategoryRepository $categories,
        private FaqQuestionRepository $questions,
        private FaqPresenter $presenter,
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

    public function categoryShow(string $slug): array
    {
        $category = $this->category($slug);

        return [
            'category' => $this->presenter->adminCategory($category),
            'audienceChoices' => FaqAudience::choices(),
        ];
    }

    public function questionShow(int $id): array
    {
        $question = $this->question($id);
        $category = $question->getCategory();

        return [
            'question' => [
                'id' => $question->getId(),
                'question' => $question->getQuestion(),
                'answer' => $question->getAnswer(),
                'visible' => $question->isVisible(),
            ],
            'category' => [
                'slug' => $category?->getSlug(),
                'name' => $category?->getName(),
            ],
        ];
    }

    public function category(string $slug): FaqCategory
    {
        $category = $this->categories->findOneBySlug($slug);

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