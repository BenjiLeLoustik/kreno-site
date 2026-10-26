<?php

namespace App\Establishment\Provider;

use App\Catalog\Presenter\CategoryPresenter;
use App\Catalog\Presenter\CityPresenter;
use App\Entity\Company\CompanyEstablishment;
use App\Establishment\Enum\AdminEstablishmentFilter;
use App\Establishment\Exception\EstablishmentNotFoundException;
use App\Establishment\Presenter\AdminEstablishmentPresenter;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Shared\Mapper\AdminQueryMapper;

class AdminEstablishmentPageProvider
{
    public function __construct(
        private CompanyEstablishmentRepository $establishments,
        private CategoryRepository $categories,
        private CityRepository $cities,
        private AdminEstablishmentPresenter $presenter,
        private CategoryPresenter $categoryPresenter,
        private CityPresenter $cityPresenter,
    ) {
    }

    public function index(
        ?string $categorySlug,
        ?string $citySlug,
        AdminEstablishmentFilter $filter,
        string $search,
        ?CompanyEstablishment $selected = null,
    ): array {
        $category = $this->find($this->categories, $categorySlug);
        $city = $this->find($this->cities, $citySlug);
        $items = $this->establishments->findForAdmin(
            $category,
            $city,
            $filter->statuses(),
            AdminQueryMapper::nullable($search),
        );

        return [
            'filters' => [
                'category' => $category?->getSlug(),
                'city' => $city?->getSlug(),
                'status' => $filter->value,
                'q' => $search,
            ],
            'categories' => $this->categoryPresenter->summaries($this->categories->findAllForAdmin()),
            'cities' => $this->cityPresenter->summaries($this->cities->findAllForAdmin()),
            'statuses' => AdminEstablishmentFilter::choices(),
            'establishments' => $this->presenter->items($items),
            'selected' => $this->detail($selected ?? $items[0] ?? null),
        ];
    }

    public function establishment(int $id): CompanyEstablishment
    {
        $establishment = $this->establishments->find($id);

        if (!$establishment instanceof CompanyEstablishment) {
            throw new EstablishmentNotFoundException();
        }

        return $establishment;
    }

    private function find(CategoryRepository|CityRepository $repository, ?string $slug): mixed
    {
        if ($slug === null) {
            return null;
        }

        if ($repository instanceof CategoryRepository) {
            return $repository->findOneByAnySlug($slug);
        }

        return $repository->findOneBySlug($slug);
    }

    private function detail(?CompanyEstablishment $establishment): ?array
    {
        if ($establishment === null) {
            return null;
        }

        return $this->presenter->detail($establishment);
    }
}