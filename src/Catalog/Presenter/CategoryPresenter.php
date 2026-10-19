<?php

declare(strict_types=1);

namespace App\Catalog\Presenter;

use App\Entity\Category;
use App\Entity\CategoryServiceSuggestion;
use App\Shared\Helper\MoneyHelper;

class CategoryPresenter
{
    public function summary(Category $category): array
    {
        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'icon' => $category->getIcon(),
        ];
    }

    public function detail(Category $category): array
    {
        return $this->summary($category) + [
                'description' => $category->getDescription(),
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