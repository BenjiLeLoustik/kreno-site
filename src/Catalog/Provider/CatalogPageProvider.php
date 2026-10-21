<?php

namespace App\Catalog\Provider;

use App\Catalog\Enum\CatalogText;
use App\Catalog\Exception\CategoryNotFoundException;
use App\Catalog\Exception\CityUnavailableException;
use App\Catalog\Presenter\CategoryPresenter;
use App\Catalog\Presenter\CityPresenter;
use App\Entity\Category;
use App\Entity\City;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use App\Repository\Company\CompanyEstablishmentRepository;
use NeoPHP\Package\Translation\TranslationManagerInterface;

class CatalogPageProvider
{
    public function __construct(
        private CityRepository $cities,
        private CategoryRepository $categories,
        private CompanyEstablishmentRepository $establishments,
        private CityPageProvider $cityPage,
        private CityPresenter $cityPresenter,
        private CategoryPresenter $categoryPresenter,
        private TranslationManagerInterface $translation,
    ) {
    }

    public function switchTarget(string $citySlug, string $categorySlug): array
    {
        $city = $this->cities->findOneOpenBySlug($citySlug);
        $category = $this->visibleCategory($categorySlug);

        if ($city !== null && $category !== null) {
            $categorySummary = $this->categoryPresenter->summary($category);

            return [
                'city_category_show', [
                    'city' => $city->getSlug(),
                    'category' => $categorySummary['slug']
                ]
            ];
        }

        if ($city !== null) {
            return [
                'city_show',
                [
                    'slug' => $city->getSlug()
                ]
            ];
        }

        if ($category !== null) {
            $categorySummary = $this->categoryPresenter->summary($category);

            return [
                'category_show',
                [
                    'slug' => $categorySummary['slug']
                ]
            ];
        }

        return [
            'home_index', []
        ];
    }

    public function city(string $slug, string $district): array
    {
        $city = $this->openCity($slug);

        return [
            'breadcrumb' => [
                $this->crumb(CatalogText::HOME->text(), 'home_index'),
                $this->crumb($city->getName()),
            ],
            'city' => $this->cityPresenter->detail($city),
            'category' => null,
            'page' => $this->cityPage->build($city, null, $this->district($district)),
            'categories' => $this->categoriesIn($city),
            'otherCities' => $this->otherCities($city),
        ];
    }

    public function cityCategory(string $citySlug, string $categorySlug, string $district): array
    {
        $city = $this->openCity($citySlug);
        $category = $this->visibleCategory($categorySlug);
        $categoryDetail = $this->categoryPresenter->detail($category);

        return [
            'breadcrumb' => [
                $this->crumb(CatalogText::HOME->text(), 'home_index'),
                $this->crumb($city->getName(), 'city_show', ['slug' => $city->getSlug()]),
                $this->crumb($categoryDetail['name']),
            ],
            'city' => $this->cityPresenter->detail($city),
            'category' => $categoryDetail,
            'page' => $this->cityPage->build($city, $category, $this->district($district)),
            'categories' => $this->categoriesIn($city, $category),
            'otherCities' => $this->otherCities($city),
        ];
    }

    public function category(string $slug): array
    {
        $category = $this->visibleCategory($slug);
        $categoryDetail = $this->categoryPresenter->detail($category);

        return [
            'breadcrumb' => [
                $this->crumb(CatalogText::HOME->text(), 'home_index'),
                $this->crumb($categoryDetail['name']),
            ],
            'category' => $categoryDetail,
            'cities' => $this->citiesFor($category),
            'otherCategories' => $this->categoryPresenter->summaries($this->otherCategories($category)),
        ];
    }

    private function openCity(string $slug): City
    {
        $city = $this->cities->findOneOpenBySlug($slug);

        if ($city === null) {
            throw new CityUnavailableException($slug);
        }

        return $city;
    }

    private function visibleCategory(string $slug): Category
    {
        $category = $this->categories->findOneVisibleBySlug(
            $slug,
            $this->translation->getLocale()
        );

        if ($category === null) {
            throw new CategoryNotFoundException($slug);
        }

        return $category;
    }

    private function crumb(?string $label, ?string $route = null, array $parameters = []): array
    {
        return [
            'label' => $label,
            'route' => $route,
            'parameters' => $parameters
        ];
    }

    private function district(string $district): ?string
    {
        $district = trim($district);

        if ($district === '') {
            return null;
        }

        return $district;
    }

    private function categoriesIn(City $city, ?Category $current = null): array
    {
        $categories = [];

        foreach ($this->categories->findVisible($this->translation->getLocale()) as $category) {
            $categories[] = $this->categoryPresenter->summary($category) + [
                    'establishmentsCount' => $this->establishments->countByCategory($category, $city, true),
                    'current' => $category === $current,
                ];
        }

        return $categories;
    }

    private function citiesFor(Category $category): array
    {
        $cities = [];

        foreach ($this->cities->findOpen() as $city) {
            $cities[] = $this->cityPresenter->summary($city) + [
                    'establishmentsCount' => $this->establishments->countByCategory($category, $city, true),
                ];
        }

        return $cities;
    }

    private function otherCities(City $current): array
    {
        $cities = [];

        foreach ($this->cities->findOpen() as $city) {
            if ($city !== $current) {
                $cities[] = $city;
            }
        }

        return $this->cityPresenter->summaries($cities);
    }

    private function otherCategories(Category $current): array
    {
        $categories = [];

        foreach ($this->categories->findVisible($this->translation->getLocale()) as $category) {
            if ($category !== $current) {
                $categories[] = $category;
            }
        }

        return $categories;
    }
}