<?php

namespace App\Controller\Front;

use App\Catalog\Presenter\CategoryPresenter;
use App\Catalog\Presenter\CityPresenter;
use App\Catalog\Provider\CityPageProvider;
use App\Entity\Category;
use App\Entity\City;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use App\Repository\Company\CompanyEstablishmentRepository;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class CatalogController extends AbstractFrontController
{
    public function __construct(
        private CityRepository $cities,
        private CategoryRepository $categories,
        private CompanyEstablishmentRepository $establishments,
        private CityPageProvider $cityPage,
        private CityPresenter $cityPresenter,
        private CategoryPresenter $categoryPresenter,
    ) {
    }

    #[Route('/city', name: 'city_switch', methods: ['GET'])]
    public function switch(Request $request): RedirectResponse
    {
        $city = $this->cities->findOneOpenBySlug($request->query->getString('city'));
        $category = $this->categories->findOneVisibleBySlug($request->query->getString('category'));

        if ($city !== null && $category !== null) {
            return $this->redirectToRoute('city_category_show', ['city' => $city->getSlug(), 'category' => $category->getSlug()]);
        }

        if ($city !== null) {
            return $this->redirectToRoute('city_show', ['slug' => $city->getSlug()]);
        }

        if ($category !== null) {
            return $this->redirectToRoute('category_show', ['slug' => $category->getSlug()]);
        }

        return $this->redirectToRoute('home_index');
    }

    #[Route('/city/{slug}', name: 'city_show', methods: ['GET'])]
    public function city(Request $request, string $slug): Response
    {
        $city = $this->findOpenCity($slug);

        return $this->render('front/city/show.html.twig', [
            'breadcrumb' => [
                ['label' => 'Accueil', 'route' => 'home_index', 'parameters' => []],
                ['label' => $city->getName(), 'route' => null, 'parameters' => []],
            ],
            'city' => $this->cityPresenter->detail($city),
            'category' => null,
            'page' => $this->cityPage->build($city, null, $this->district($request)),
            'categories' => $this->categoriesIn($city),
            'otherCities' => $this->otherCities($city),
        ]);
    }

    #[Route('/city/{city}/{category}', name: 'city_category_show', methods: ['GET'])]
    public function cityCategory(Request $request, string $city, string $category): Response
    {
        $cityEntity = $this->findOpenCity($city);
        $categoryEntity = $this->findVisibleCategory($category);

        return $this->render('front/city/show.html.twig', [
            'breadcrumb' => [
                ['label' => 'Accueil', 'route' => 'home_index', 'parameters' => []],
                ['label' => $cityEntity->getName(), 'route' => 'city_show', 'parameters' => ['slug' => $cityEntity->getSlug()]],
                ['label' => $categoryEntity->getName(), 'route' => null, 'parameters' => []],
            ],
            'city' => $this->cityPresenter->detail($cityEntity),
            'category' => $this->categoryPresenter->detail($categoryEntity),
            'page' => $this->cityPage->build($cityEntity, $categoryEntity, $this->district($request)),
            'categories' => $this->categoriesIn($cityEntity, $categoryEntity),
            'otherCities' => $this->otherCities($cityEntity),
        ]);
    }

    #[Route('/category/{slug}', name: 'category_show', methods: ['GET'])]
    public function category(string $slug): Response
    {
        $category = $this->findVisibleCategory($slug);

        return $this->render('front/category/show.html.twig', [
            'breadcrumb' => [
                ['label' => 'Accueil', 'route' => 'home_index', 'parameters' => []],
                ['label' => $category->getName(), 'route' => null, 'parameters' => []],
            ],
            'category' => $this->categoryPresenter->detail($category),
            'cities' => array_map(fn (City $city): array => $this->cityPresenter->summary($city) + [
                    'establishmentsCount' => $this->establishments->countByCategory($category, $city, true),
                ], $this->cities->findOpen()),
            'otherCategories' => $this->categoryPresenter->summaries(array_values(array_filter($this->categories->findVisible(), static fn (Category $other): bool => $other !== $category))),
        ]);
    }

    private function district(Request $request): ?string
    {
        $district = trim($request->query->getString('district'));

        return $district !== '' ? $district : null;
    }

    private function categoriesIn(City $city, ?Category $current = null): array
    {
        return array_map(fn (Category $category): array => $this->categoryPresenter->summary($category) + [
                'establishmentsCount' => $this->establishments->countByCategory($category, $city, true),
                'current' => $category === $current,
            ], $this->categories->findVisible());
    }

    private function otherCities(City $city): array
    {
        return $this->cityPresenter->summaries(array_values(array_filter($this->cities->findOpen(), static fn (City $other): bool => $other !== $city)));
    }

    private function findOpenCity(string $slug): City
    {
        return $this->cities->findOneOpenBySlug($slug) ?? throw $this->createNotFoundException('La ville {slug} n\'est pas disponible.', ['slug' => $slug]);
    }

    private function findVisibleCategory(string $slug): Category
    {
        return $this->categories->findOneVisibleBySlug($slug) ?? throw $this->createNotFoundException('La catégorie {slug} n\'existe pas.', ['slug' => $slug]);
    }
}