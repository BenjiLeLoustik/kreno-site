<?php

namespace App\Establishment\Provider;

use App\Catalog\Presenter\CityPresenter;
use App\Entity\Category;
use App\Entity\City;
use App\Establishment\Dto\SlotSearchInput;
use App\Establishment\Enum\SearchSort;
use App\Establishment\Enum\SearchText;
use App\Establishment\Helper\TimeWindowHelper;
use App\Establishment\Presenter\SlotPresenter;
use App\Establishment\Service\SlotSearchService;
use App\Repository\CategoryRepository;
use DateTimeImmutable;
use NeoPHP\Package\Translation\TranslationManagerInterface;

class SearchPageProvider
{
    public function __construct(
        private SlotSearchService $search,
        private CategoryRepository $categories,
        private SlotPresenter $slotPresenter,
        private CityPresenter $cityPresenter,
        private TranslationManagerInterface $translation,
    ) {
    }

    public function index(SlotSearchInput $input): array
    {
        $now = new DateTimeImmutable();
        $result = $this->search->search($input, $now);
        $city = $result->getCity();

        return [
            'city' => $this->city($city),
            'title' => $this->title($city, $result->count()),
            'count' => $result->count(),
            'located' => $input->latitude !== null && $input->longitude !== null,
            'filters' => [
                'categories' => $this->categories($result->getCategories()),
                'when' => TimeWindowHelper::choices($input->when),
                'distance' => $input->distance,
                'sort' => SearchSort::choices($input->sort),
                'latitude' => $result->getLatitude(),
                'longitude' => $result->getLongitude(),
            ],
            'slots' => $this->slotPresenter->cards($result->getMatches(), $now),
        ];
    }

    private function city(?City $city): ?array
    {
        if ($city === null) {
            return null;
        }

        return $this->cityPresenter->summary($city);
    }

    private function title(?City $city, int $count): string
    {
        if ($city === null) {
            return SearchText::NO_CITY->text();
        }

        return SearchText::results($count)->text($count, $city->getName());
    }

    private function categories(array $selectedCategories): array
    {
        $selected = array_map(static fn (Category $category): ?string => $category->getSlug(), $selectedCategories);
        $categories = [];

        foreach ($this->categories->findVisible($this->translation->getLocale()) as $category) {
            $categories[] = [
                'slug' => $category->getSlug(),
                'label' => $category->getName(),
                'selected' => $selected === [] || in_array($category->getSlug(), $selected, true),
            ];
        }

        return $categories;
    }
}