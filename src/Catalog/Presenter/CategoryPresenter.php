<?php

declare(strict_types=1);

namespace App\Catalog\Presenter;

use App\Entity\Category;
use App\Entity\CategoryServiceSuggestion;
use App\Shared\Helper\MoneyHelper;
use App\Shared\Service\TranslatableResolver;

class CategoryPresenter
{
    public function __construct(
        private TranslatableResolver $resolver,
    ) {
    }

    public function summary(Category $category): array
    {
        $translation = $this->resolver->category($category);

        return [
            'id' => $category->getId(),
            'name' => $translation?->getName(),
            'slug' => $translation?->getSlug(),
            'icon' => $category->getIcon(),
        ];
    }

    public function detail(Category $category): array
    {
        $translation = $this->resolver->category($category);

        return $this->summary($category) + [
                'description' => $translation?->getDescription(),
                'visible' => $category->isVisible(),
                'position' => $category->getPosition(),
                'suggestions' => $this->suggestions($category),
            ];
    }

    public function summaries(array $categories): array
    {
        return array_map(fn (Category $category): array => $this->summary($category), $categories);
    }

    public function suggestions(?Category $category): array
    {
        if ($category === null) {
            return [];
        }

        $suggestions = [];

        foreach ($category->getServiceSuggestions() as $suggestion) {
            $suggestions[] = $this->suggestion($suggestion);
        }

        return $suggestions;
    }

    private function suggestion(CategoryServiceSuggestion $suggestion): array
    {
        return [
            'id' => $suggestion->getId(),
            'name' => $suggestion->getName(),
            'duration' => $suggestion->getDuration(),
            'price' => $suggestion->getPrice(),
            'priceInput' => number_format($suggestion->getPrice() / 100, 2, '.', ''),
            'priceLabel' => MoneyHelper::formatExact($suggestion->getPrice()),
        ];
    }
}