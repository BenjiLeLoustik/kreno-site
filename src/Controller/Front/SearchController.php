<?php

namespace App\Controller\Front;

use App\Catalog\Presenter\CityPresenter;
use App\Entity\Category;
use App\Establishment\Dto\SlotSearchInput;
use App\Establishment\Helper\TimeWindowHelper;
use App\Establishment\Presenter\SlotPresenter;
use App\Establishment\Service\SlotSearchService;
use App\Repository\CategoryRepository;
use DateTimeImmutable;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapQueryString;

class SearchController extends AbstractFrontController
{
    public const SORTS = ['soonest' => 'Le plus tôt', 'nearest' => 'Le plus proche', 'discount' => 'La plus grosse remise'];

    public function __construct(
        private SlotSearchService $search,
        private CategoryRepository $categories,
        private SlotPresenter $slotPresenter,
        private CityPresenter $cityPresenter,
    ) {
    }

    #[Route('/search', name: 'search_index', methods: ['GET'])]
    public function index(
        #[MapQueryString]
        ?SlotSearchInput $input = null
    ): Response {
        $input ??= new SlotSearchInput();
        $now = new DateTimeImmutable();
        $result = $this->search->search($input, $now);
        $selected = array_map(static fn (Category $category): ?string => $category->getSlug(), $result->getCategories());
        $city = $result->getCity();

        return $this->render('front/search/index.html.twig', [
            'city' => $city !== null ? $this->cityPresenter->summary($city) : null,
            'title' => $city !== null ? sprintf('%d créneau%s libre%s à %s', $result->count(), $result->count() > 1 ? 'x' : '', $result->count() > 1 ? 's' : '', $city->getName()) : 'Aucune ville disponible',
            'count' => $result->count(),
            'located' => $input->latitude !== null && $input->longitude !== null,
            'filters' => [
                'categories' => array_map(static fn (Category $category): array => [
                    'slug' => $category->getSlug(),
                    'label' => $category->getName(),
                    'selected' => $selected === [] || in_array($category->getSlug(), $selected, true),
                ], $this->categories->findVisible()),
                'when' => TimeWindowHelper::choices($input->when),
                'distance' => $input->distance,
                'sort' => array_map(static fn (string $key, string $label): array => ['key' => $key, 'label' => $label, 'selected' => $key === $input->sort], array_keys(self::SORTS), self::SORTS),
                'latitude' => $result->getLatitude(),
                'longitude' => $result->getLongitude(),
            ],
            'slots' => $this->slotPresenter->cards($result->getMatches(), $now),
        ]);
    }
}