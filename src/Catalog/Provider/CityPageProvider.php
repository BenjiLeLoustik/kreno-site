<?php

namespace App\Catalog\Provider;

use App\Entity\Category;
use App\Entity\City;
use App\Entity\Company\CompanyEstablishment;
use App\Establishment\Dto\SlotSearchInput;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Model\SlotMatch;
use App\Establishment\Presenter\SlotPresenter;
use App\Establishment\Service\SlotSearchService;
use App\Repository\Company\CompanyEstablishmentRepository;
use DateTimeImmutable;

class CityPageProvider
{
    public const SLOTS = 12;

    public const SHOPS = 24;

    private const SCAN = 100;

    public function __construct(
        private SlotSearchService $search,
        private CompanyEstablishmentRepository $establishments,
        private SlotPresenter $slotPresenter,
    ) {
    }

    public function build(City $city, ?Category $category, ?string $district, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $input = new SlotSearchInput();
        $input->where = $city->getSlug();
        $input->what = $category?->getSlug();
        $input->limit = self::SCAN;

        $matches = $this->search->search($input, $now)->getMatches();
        $establishments = $this->establishments->findForAdmin($category, $city, [EstablishmentStatus::ONLINE], null, self::SHOPS);
        $districts = $this->districts($establishments);
        $district = $district !== null && in_array($district, $districts, true) ? $district : null;
        $free = [];

        foreach ($matches as $match) {
            $id = $match->getSlot()->getEstablishment()?->getId();
            $free[$id] = ($free[$id] ?? 0) + 1;
        }

        $filtered = $district === null ? $matches : array_values(array_filter($matches, static fn (SlotMatch $match): bool => $match->getSlot()->getEstablishment()?->getLocality() === $district));

        return [
            'count' => count($filtered),
            'slots' => $this->slotPresenter->cards(array_slice($filtered, 0, self::SLOTS), $now),
            'district' => $district,
            'districts' => $districts,
            'shops' => array_map(static fn (CompanyEstablishment $establishment): array => [
                'name' => $establishment->getName(),
                'locality' => $establishment->getLocality(),
                'category' => $establishment->getCategory()?->getName(),
                'city' => $establishment->getCity()?->getSlug(),
                'slug' => $establishment->getSlug(),
                'free' => $free[$establishment->getId()] ?? 0,
            ], $establishments),
        ];
    }

    private function districts(array $establishments): array
    {
        $districts = array_values(array_unique(array_filter(array_map(static fn (CompanyEstablishment $establishment): ?string => $establishment->getLocality(), $establishments))));
        natcasesort($districts);

        return array_values($districts);
    }
}