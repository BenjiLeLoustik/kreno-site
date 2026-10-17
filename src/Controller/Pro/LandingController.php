<?php

namespace App\Controller\Pro;

use App\Billing\Mapper\SubscriptionRequestMapper;
use App\Website\Provider\ProLandingPageProvider;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class LandingController extends AbstractController
{
    public function __construct(
        private ProLandingPageProvider $pages,
    ) {
    }

    #[Route('/pros', name: 'pro_landing', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $this->pages->index(SubscriptionRequestMapper::period($request), $this->getUser());

        return $this->render('pro/landing/index.html.twig', $page);
    }
}