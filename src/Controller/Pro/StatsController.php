<?php

namespace App\Controller\Pro;

use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Establishment\Provider\ProStatsProvider;
use App\Repository\Company\CompanySubscriptionRepository;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class StatsController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        private ProStatsProvider $stats,
        private CompanySubscriptionRepository $subscriptions,
    ) {
        parent::__construct($context, $resolver);
    }

    #[Route('/pro/stats', name: 'pro_stats', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $establishment = $this->currentEstablishment();
        $this->requirePermission(Permission::PLANNING_VIEW, $establishment);
        $company = $establishment->getCompany();
        $advanced = $company !== null && $this->subscriptions->findCurrent($company)?->getPlan()?->isAdvancedStats() === true;

        return $this->render('pro/stats/index.html.twig', [
            'context' => $this->proContext(),
            'stats' => $this->stats->build($establishment, $request->query->getInt('period', ProStatsProvider::DEFAULT_PERIOD), $advanced),
        ]);
    }
}