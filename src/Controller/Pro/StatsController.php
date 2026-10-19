<?php

namespace App\Controller\Pro;

use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Provider\ProContextProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Establishment\Provider\ProStatsPageProvider;
use App\Establishment\Provider\ProStatsProvider;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class StatsController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        ProContextProvider $pro,
        private ProStatsPageProvider $pages,
    ) {
        parent::__construct($context, $resolver, $pro);
    }

    #[Route('/pro/stats', name: 'pro_stats', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $establishment = $this->permittedEstablishment(Permission::PLANNING_VIEW);
        $period = $request->query->getInt('period', ProStatsProvider::DEFAULT_PERIOD);

        return $this->renderPro('pro/stats/index.html.twig', $this->pages->index($establishment, $period));
    }
}