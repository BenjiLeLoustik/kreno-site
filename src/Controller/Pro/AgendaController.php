<?php

namespace App\Controller\Pro;

use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Establishment\Helper\CalendarHelper;
use App\Establishment\Provider\AgendaProvider;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class AgendaController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        private AgendaProvider $agenda,
    ) {
        parent::__construct($context, $resolver);
    }

    #[Route('/pro/agenda', name: 'pro_agenda', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $member = $this->currentMember();
        $establishment = $this->currentEstablishment();
        $this->requirePermission(Permission::PLANNING_VIEW, $establishment);
        $week = $request->query->getString('week');

        return $this->render('pro/agenda/index.html.twig', [
            'context' => $this->proContext(),
            'agenda' => $this->agenda->week($establishment, CalendarHelper::parseWeek($week !== '' ? $week : null)),
            'canPublish' => $this->resolver->memberCan($member, Permission::SLOTS_MANAGE, $establishment),
            'canManageBookings' => $this->resolver->memberCan($member, Permission::BOOKINGS_MANAGE, $establishment),
        ]);
    }
}