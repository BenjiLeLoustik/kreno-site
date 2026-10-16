<?php

namespace App\Controller\Front;

use App\Catalog\Presenter\CityPresenter;
use App\Establishment\Dto\SlotSearchInput;
use App\Establishment\Presenter\SlotPresenter;
use App\Establishment\Provider\HomeProvider;
use App\Establishment\Service\SlotSearchService;
use DateTimeImmutable;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class HomeController extends AbstractFrontController
{
    public const HOME_SLOTS = 6;

    public function __construct(
        private SlotSearchService $search,
        private HomeProvider $home,
        private SlotPresenter $slotPresenter,
        private CityPresenter $cityPresenter,
    ) {
    }

    #[Route('/', name: 'home_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $now = new DateTimeImmutable();
        $input = new SlotSearchInput();
        $input->where = $request->query->getString('city') !== '' ? $request->query->getString('city') : null;
        $input->limit = self::HOME_SLOTS;
        $result = $this->search->search($input, $now);

        return $this->render('front/home/index.html.twig', [
            'city' => $result->getCity() !== null ? $this->cityPresenter->summary($result->getCity()) : null,
            'slots' => $this->slotPresenter->cards($result->getMatches(), $now),
            'steps' => HomeProvider::STEPS,
            'proFacts' => $this->home->proFacts(),
        ]);
    }
}