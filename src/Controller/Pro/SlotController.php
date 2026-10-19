<?php

namespace App\Controller\Pro;

use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Provider\ProContextProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Establishment\EstablishmentSlot;
use App\Establishment\Dto\SlotInput;
use App\Establishment\Enum\SlotFlash;
use App\Establishment\Mapper\SlotRequestMapper;
use App\Establishment\Provider\ProSlotPageProvider;
use App\Establishment\Service\SlotPublicationService;
use App\Shared\Helper\DateHelper;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class SlotController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        ProContextProvider $pro,
        private SlotPublicationService $publication,
        private ProSlotPageProvider $pages,
    ) {
        parent::__construct($context, $resolver, $pro);
    }

    #[Route('/pro/slots', name: 'pro_slots', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $member = $this->currentMember();
        $establishment = $this->permittedEstablishment(Permission::PLANNING_VIEW);
        $page = $this->pages->index($member, $establishment, SlotRequestMapper::filter($request));

        return $this->renderPro('pro/slot/index.html.twig', $page);
    }

    #[Route('/pro/slots/new', name: 'pro_slot_new', methods: ['GET'])]
    public function new(Request $request): Response
    {
        $establishment = $this->permittedEstablishment(Permission::SLOTS_MANAGE);
        $page = $this->pages->form(
            $establishment,
            SlotRequestMapper::sourceId($request),
            SlotRequestMapper::serviceId($request),
            SlotRequestMapper::day($request),
        );

        return $this->renderPro('pro/slot/new.html.twig', $page);
    }

    #[Csrf('pro')]
    #[Route('/pro/slots', name: 'pro_slot_publish', methods: ['POST'])]
    public function publish(#[MapRequestPayload(validate: false)] SlotInput $input): RedirectResponse
    {
        $establishment = $this->currentEstablishment();
        $member = $this->requirePermission(Permission::SLOTS_MANAGE, $establishment);

        if ($this->invalid($input)) {
            return $this->redirectToRoute('pro_slot_new', SlotRequestMapper::retry($input));
        }

        $slot = null;
        $this->attempt(function () use ($establishment, $member, $input, &$slot): void {
            $slot = $this->publication->publish($establishment, $member->getUser() ?? $this->currentUser(), $input);
        });

        if (!$slot instanceof EstablishmentSlot) {
            return $this->redirectToRoute('pro_slot_new', SlotRequestMapper::retry($input));
        }

        $this->addFlash('success', SlotFlash::PUBLISHED->text(
            DateHelper::time($slot->getStartsAt()),
            DateHelper::time($slot->getVisibleUntil()),
        ));

        return $this->redirectToRoute('pro_slot_new', SlotRequestMapper::next($input));
    }

    #[Csrf('pro')]
    #[Route('/pro/slots/{id}/withdraw', name: 'pro_slot_withdraw', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function withdraw(Request $request, int $id): RedirectResponse
    {
        $establishment = $this->currentEstablishment();
        $member = $this->requirePermission(Permission::SLOTS_MANAGE, $establishment);
        $slot = $this->pages->owned($establishment, $id);
        $this->attempt(
            fn () => $this->publication->withdraw($slot, $member->getUser()),
            SlotFlash::WITHDRAWN->text(DateHelper::time($slot->getStartsAt())),
        );

        return $this->redirectToRoute('pro_slots', SlotRequestMapper::listQuery($request));
    }
}