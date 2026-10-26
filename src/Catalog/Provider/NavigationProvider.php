<?php

namespace App\Catalog\Provider;

use App\Catalog\Presenter\CategoryPresenter;
use App\Catalog\Presenter\CityPresenter;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use NeoPHP\Package\Translation\TranslationManagerInterface;

class NavigationProvider
{
    private ?array $navigation = null;

    public function __construct(
        private CityRepository $cities,
        private CategoryRepository $categories,
        private CityPresenter $cityPresenter,
        private CategoryPresenter $categoryPresenter,
        private TranslationManagerInterface $translation,
    ) {
    }

    public function get(): array
    {
        return $this->navigation ??= [
            'categories' => $this->categoryPresenter->summaries(
                $this->categories->findVisible($this->translation->getLocale())
            ),
            'cities' => $this->cityPresenter->summaries($this->cities->findOpen()),
        ];
    }
}