<?php

namespace App\Catalog\Presenter;

use App\Entity\Category;

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
            ];
    }

    public function summaries(array $categories): array
    {
        return array_map(fn (Category $category): array => $this->summary($category), $categories);
    }
}