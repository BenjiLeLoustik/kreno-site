<?php

namespace App\Controller\Front;

use App\Establishment\Dto\SlotSearchInput;
use App\Establishment\Provider\SearchPageProvider;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapQueryString;

class SearchController extends AbstractFrontController
{
    public function __construct(
        private SearchPageProvider $pages,
    ) {
    }

    #[Route('/search', name: 'search_index', methods: ['GET'])]
    public function index(
        #[MapQueryString]
        ?SlotSearchInput $input = null
    ): Response {
        return $this->render('front/search/index.html.twig', $this->pages->index($input ?? new SlotSearchInput()));
    }
}