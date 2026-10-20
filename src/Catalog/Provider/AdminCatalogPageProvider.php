<?php

namespace App\Catalog\Provider;

use App\Catalog\Enum\CityStatus;
use App\Catalog\Exception\AdminCityNotFoundException;
use App\Catalog\Exception\CategoryNotFoundException;
use App\Catalog\Exception\CityStatusNotFoundException;
use App\Catalog\Exception\ServiceSuggestionNotFoundException;
use App\Catalog\Presenter\CategoryPresenter;
use App\Catalog\Presenter\CityPresenter;
use App\Entity\Category;
use App\Entity\CategoryServiceSuggestion;
use App\Entity\City;
use App\Establishment\Helper\ServiceSuggestionHelper;
use App\Repository\CategoryRepository;
use App\Repository\CategoryServiceSuggestionRepository;
use App\Repository\CityRepository;
use App\Repository\Company\CompanyEstablishmentRepository;

class AdminCatalogPageProvider
{
    public function __construct(
        private CityRepository $cities,
        private CategoryRepository $categories,
        private CategoryServiceSuggestionRepository $suggestions,
        private CompanyEstablishmentRepository $establishments,
        private CityPresenter $cityPresenter,
        private CategoryPresenter $categoryPresenter,
    ) {
    }

    public function index(): array
    {
        return [
            'categories' => $this->categoryRows(),
            'cities' => $this->cityRows(),
            'cityStatuses' => $this->cityStatuses(),
            'durations' => ServiceSuggestionHelper::DURATIONS,
        ];
    }

    public function categoryShow(string $slug): array
    {
        $category = $this->category($slug);

        return [
            'category' => $this->categoryPresenter->detail($category),
            'establishmentsCount' => $this->establishments->countByCategory($category),
            'durations' => ServiceSuggestionHelper::DURATIONS,
        ];
    }

    public function category(string $slug): Category
    {
        $category = $this->categories->findOneBySlug($slug);

        if ($category === null) {
            throw new CategoryNotFoundException($slug);
        }

        return $category;
    }

    public function suggestion(int $id): CategoryServiceSuggestion
    {
        $suggestion = $this->suggestions->findOneById($id);

        if ($suggestion === null) {
            throw new ServiceSuggestionNotFoundException($id);
        }

        return $suggestion;
    }

    public function city(string $slug): City
    {
        $city = $this->cities->findOneBySlug($slug);

        if ($city === null) {
            throw new AdminCityNotFoundException($slug);
        }

        return $city;
    }

    public function cityStatus(string $status): CityStatus
    {
        $cityStatus = CityStatus::tryFrom($status);

        if ($cityStatus === null) {
            throw new CityStatusNotFoundException($status);
        }

        return $cityStatus;
    }

    private function categoryRows(): array
    {
        $rows = [];

        foreach ($this->categories->findAllForAdmin() as $category) {
            $rows[] = $this->categoryPresenter->detail($category) + [
                    'establishmentsCount' => $this->establishments->countByCategory($category),
                ];
        }

        return $rows;
    }

    private function cityRows(): array
    {
        $rows = [];

        foreach ($this->cities->findAllForAdmin() as $city) {
            $rows[] = $this->cityPresenter->detail($city) + [
                    'establishmentsCount' => $this->establishments->countByCity($city),
                ];
        }

        return $rows;
    }

    private function cityStatuses(): array
    {
        $statuses = [];

        foreach (CityStatus::cases() as $status) {
            $statuses[] = [
                'value' => $status->value,
                'label' => $status->label()
            ];
        }

        return $statuses;
    }
}