<?php

namespace App\Controller\Front;

use App\Establishment\Provider\HomeProvider;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class HomeController extends AbstractFrontController
{
    public function __construct(
        private HomeProvider $home,
    ) {
    }

    #[Route('/', name: 'home_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        return $this->render('front/home/index.html.twig', $this->home->page($request->query->getString('city')));
    }
}