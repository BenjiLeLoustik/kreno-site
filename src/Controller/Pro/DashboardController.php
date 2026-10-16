<?php

namespace App\Controller\Pro;

use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Provider\ProDashboardProvider;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class DashboardController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        private ProDashboardProvider $dashboard,
    ) {
        parent::__construct($context, $resolver);
    }

    #[Route('/pro', name: 'pro_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->currentUser();
        $member = $this->context->getMembership($user);

        if ($member === null || $user->getEmailVerifiedAt() === null) {
            return $this->redirectToRoute('pro_signup');
        }

        $establishment = $this->context->getCurrentEstablishment($member);

        if ($establishment === null) {
            return $this->redirectToRoute('pro_signup');
        }

        $this->requirePermission(Permission::PLANNING_VIEW, $establishment);

        return $this->render('pro/dashboard/index.html.twig', [
            'context' => $this->proContext(),
            'establishment' => [
                'id' => $establishment->getId(),
                'name' => $establishment->getName(),
                'status' => $establishment->getStatus()->value,
                'statusLabel' => $establishment->getStatus()->label(),
                'online' => $establishment->getStatus() === EstablishmentStatus::ONLINE,
                'rejectionReason' => $establishment->getRejectionReason(),
            ],
            'canPublish' => $this->resolver->memberCan($member, Permission::SLOTS_MANAGE, $establishment),
            'dashboard' => $this->dashboard->build($establishment, $user),
        ]);
    }
}