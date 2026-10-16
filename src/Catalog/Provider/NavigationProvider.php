<?php

namespace App\Catalog\Provider;

use App\Catalog\Presenter\CategoryPresenter;
use App\Catalog\Presenter\CityPresenter;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;

class NavigationProvider
{
    private ?array $navigation = null;

    public function __construct(
        private CityRepository $cities,
        private CategoryRepository $categories,
        private CityPresenter $cityPresenter,
        private CategoryPresenter $categoryPresenter,
    ) {
    }

    public function get(): array
    {
        return $this->navigation ??= [
            'categories' => $this->categoryPresenter->summaries($this->categories->findVisible()),
            'cities' => $this->cityPresenter->summaries($this->cities->findOpen()),
        ];
    }
}