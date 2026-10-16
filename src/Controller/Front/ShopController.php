<?php

namespace App\Controller\Front;

use App\Establishment\Mapper\ShopRequestMapper;
use App\Establishment\Provider\ShopPageProvider;
use App\Establishment\Provider\SlotPageProvider;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\JsonResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class ShopController extends AbstractFrontController
{
    public function __construct(
        private ShopPageProvider $shops,
        private SlotPageProvider $slots,
    ) {
    }

    #[Route(
        '/shop/{city}/{slug}',
        name: 'shop_show',
        methods: ['GET'],
        requirements: ['city' => '[a-z0-9-]+', 'slug' => '[a-z0-9-]+'],
    )]
    public function show(Request $request, string $city, string $slug): Response
    {
        $page = $this->shops->show($city, $slug, $this->optionalUser(), ShopRequestMapper::origin($request));

        return $this->render('front/shop/show.html.twig', $page);
    }

    #[Route(
        '/shop/{city}/{slug}/follow',
        name: 'shop_follow',
        methods: ['POST'],
        requirements: ['city' => '[a-z0-9-]+', 'slug' => '[a-z0-9-]+'],
    )]
    public function follow(string $city, string $slug): JsonResponse
    {
        return $this->json($this->shops->follow($city, $slug, $this->currentUser()));
    }

    #[Route('/slot/{id}', name: 'slot_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function slot(Request $request, int $id): Response
    {
        $page = $this->slots->show($id, $this->optionalUser(), ShopRequestMapper::origin($request));

        return $this->render('front/slot/show.html.twig', $page);
    }
}