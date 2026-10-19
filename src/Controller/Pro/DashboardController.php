<?php

namespace App\Controller\Pro;

use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Provider\ProContextProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Establishment\Provider\ProDashboardPageProvider;
use App\Establishment\Provider\ProEntryProvider;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class DashboardController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        ProContextProvider $pro,
        private ProEntryProvider $entry,
        private ProDashboardPageProvider $pages,
    ) {
        parent::__construct($context, $resolver, $pro);
    }

    #[Route('/pro', name: 'pro_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->currentUser();
        $establishment = $this->entry->dashboardEstablishment($user);

        if ($establishment === null) {
            return $this->redirectToRoute('pro_signup');
        }

        $member = $this->requirePermission(Permission::PLANNING_VIEW, $establishment);

        return $this->renderPro('pro/dashboard/index.html.twig', $this->pages->index($member, $establishment, $user));
    }
}