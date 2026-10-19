<?php

namespace App\Controller\Front;

use App\Catalog\Provider\CatalogPageProvider;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class CatalogController extends AbstractFrontController
{
    public function __construct(
        private CatalogPageProvider $pages,
    ) {
    }

    #[Route('/city', name: 'city_switch', methods: ['GET'])]
    public function switch(Request $request): RedirectResponse
    {
        [$route, $parameters] = $this->pages->switchTarget(
            $request->query->getString('city'),
            $request->query->getString('category'),
        );

        return $this->redirectToRoute($route, $parameters);
    }

    #[Route('/city/{slug}', name: 'city_show', methods: ['GET'])]
    public function city(Request $request, string $slug): Response
    {
        $page = $this->pages->city($slug, $request->query->getString('district'));

        return $this->render('front/city/show.html.twig', $page);
    }

    #[Route('/city/{city}/{category}', name: 'city_category_show', methods: ['GET'])]
    public function cityCategory(Request $request, string $city, string $category): Response
    {
        $page = $this->pages->cityCategory($city, $category, $request->query->getString('district'));

        return $this->render('front/city/show.html.twig', $page);
    }

    #[Route('/category/{slug}', name: 'category_show', methods: ['GET'])]
    public function category(string $slug): Response
    {
        return $this->render('front/category/show.html.twig', $this->pages->category($slug));
    }
}