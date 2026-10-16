<?php

namespace App\Establishment\Provider;

use App\Catalog\Enum\CatalogText;
use App\Catalog\Exception\CityNotFoundException;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\User\User;
use App\Establishment\Enum\FollowLabel;
use App\Establishment\Exception\PublicEstablishmentNotFoundException;
use App\Establishment\Presenter\EstablishmentPagePresenter;
use App\Establishment\Service\FollowService;
use App\Repository\CityRepository;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Repository\Establishment\EstablishmentFollowerRepository;

class ShopPageProvider
{
    public function __construct(
        private CityRepository $cities,
        private CompanyEstablishmentRepository $establishments,
        private EstablishmentFollowerRepository $followers,
        private FollowService $follows,
        private EstablishmentPagePresenter $presenter,
    ) {
    }

    public function show(string $city, string $slug, ?User $user, array $origin): array
    {
        $establishment = $this->establishment($city, $slug);
        [$latitude, $longitude] = $origin;

        return [
            'breadcrumb' => $this->breadcrumb($establishment),
            'establishment' => $this->presenter->publicPage($establishment, $user, $latitude, $longitude),
        ];
    }

    public function follow(string $city, string $slug, User $user): array
    {
        $establishment = $this->establishment($city, $slug);
        $following = $this->follows->toggle($establishment, $user);

        return [
            'following' => $following,
            'label' => FollowLabel::of($following)->label(),
            'followersCount' => $this->followers->countForEstablishment($establishment),
        ];
    }

    public function establishment(string $city, string $slug): CompanyEstablishment
    {
        $cityEntity = $this->cities->findOneBySlug($city);

        if ($cityEntity === null) {
            throw new CityNotFoundException();
        }

        $establishment = $this->establishments->findOnePublic($cityEntity, $slug);

        if ($establishment === null) {
            throw new PublicEstablishmentNotFoundException();
        }

        return $establishment;
    }

    public function breadcrumb(CompanyEstablishment $establishment): array
    {
        $city = $establishment->getCity();
        $category = $establishment->getCategory();
        $items = [$this->crumb(CatalogText::HOME->text(), 'home_index')];

        if ($city !== null) {
            $items[] = $this->crumb($city->getName(), 'city_show', ['slug' => $city->getSlug()]);
        }

        if ($city !== null && $category !== null) {
            $parameters = ['city' => $city->getSlug(), 'category' => $category->getSlug()];
            $items[] = $this->crumb($category->getName(), 'city_category_show', $parameters);
        }

        $items[] = $this->crumb($establishment->getName());

        return $items;
    }

    private function crumb(?string $label, ?string $route = null, array $parameters = []): array
    {
        return ['label' => $label, 'route' => $route, 'parameters' => $parameters];
    }
}