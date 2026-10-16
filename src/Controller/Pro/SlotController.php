<?php

namespace App\Controller\Pro;

use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Establishment\EstablishmentMember;
use App\Entity\Establishment\EstablishmentSlot;
use App\Establishment\Dto\SlotInput;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Enum\SlotStatus;
use App\Establishment\Helper\CalendarHelper;
use App\Establishment\Presenter\ServicePresenter;
use App\Establishment\Presenter\SlotPresenter;
use App\Establishment\Service\SlotPublicationService;
use App\Repository\Establishment\EstablishmentMemberRepository;
use App\Repository\Establishment\EstablishmentServiceRepository;
use App\Repository\Establishment\EstablishmentSlotRepository;
use App\Shared\Helper\DateHelper;
use App\User\Helper\UserNameHelper;
use DateTimeImmutable;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class SlotController extends AbstractProController
{
    public const FILTERS = [
        'all' => ['label' => 'Tous', 'statuses' => [SlotStatus::AVAILABLE, SlotStatus::BOOKED, SlotStatus::EXPIRED, SlotStatus::WITHDRAWN]],
        'online' => ['label' => 'En ligne', 'statuses' => [SlotStatus::AVAILABLE]],
        'booked' => ['label' => 'Réservés', 'statuses' => [SlotStatus::BOOKED]],
        'expired' => ['label' => 'Expirés et retirés', 'statuses' => [SlotStatus::EXPIRED, SlotStatus::WITHDRAWN]],
    ];

    public const HISTORY_DAYS = 7;

    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        private SlotPublicationService $publication,
        private EstablishmentSlotRepository $slots,
        private EstablishmentServiceRepository $services,
        private EstablishmentMemberRepository $staff,
        private SlotPresenter $slotPresenter,
        private ServicePresenter $servicePresenter,
    ) {
        parent::__construct($context, $resolver);
    }

    #[Route('/pro/slots', name: 'pro_slots', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $member = $this->currentMember();
        $establishment = $this->currentEstablishment();
        $this->requirePermission(Permission::PLANNING_VIEW, $establishment);

        $filter = $request->query->getString('filter', 'all');
        $filter = isset(self::FILTERS[$filter]) ? $filter : 'all';
        $now = new DateTimeImmutable();
        $since = $now->setTime(0, 0)->modify(sprintf('-%d days', self::HISTORY_DAYS));
        $tabs = [];

        foreach (self::FILTERS as $key => $definition) {
            $tabs[] = ['key' => $key, 'label' => $definition['label'], 'count' => $this->slots->countForPro($establishment, $definition['statuses'], $since)];
        }

        return $this->render('pro/slot/index.html.twig', [
            'context' => $this->proContext(),
            'filter' => $filter,
            'tabs' => $tabs,
            'rows' => array_map(fn (EstablishmentSlot $slot): array => $this->slotPresenter->forPro($slot, $now), $this->slots->findForPro($establishment, self::FILTERS[$filter]['statuses'], $since)),
            'historyDays' => self::HISTORY_DAYS,
            'canPublish' => $this->resolver->memberCan($member, Permission::SLOTS_MANAGE, $establishment),
        ]);
    }

    #[Route('/pro/slots/new', name: 'pro_slot_new', methods: ['GET'])]
    public function new(Request $request): Response
    {
        $establishment = $this->currentEstablishment();
        $this->requirePermission(Permission::SLOTS_MANAGE, $establishment);

        $now = new DateTimeImmutable();
        $source = $request->query->getInt('from') > 0 ? $this->slots->findOneInEstablishment($establishment, $request->query->getInt('from')) : null;
        $defaults = $this->publication->defaults($establishment, $source?->getService());
        $days = [];

        for ($offset = 0; $offset <= $defaults['publicationWindowDays']; ++$offset) {
            $day = $now->setTime(0, 0)->modify(sprintf('+%d days', $offset));
            $days[] = ['value' => $day->format('Y-m-d'), 'label' => CalendarHelper::dayLabel($day, $now)];
        }

        return $this->render('pro/slot/new.html.twig', [
            'context' => $this->proContext(),
            'establishment' => [
                'name' => $establishment->getName(),
                'locality' => $establishment->getLocality(),
                'online' => $establishment->getStatus() === EstablishmentStatus::ONLINE,
                'statusLabel' => $establishment->getStatus()->label(),
            ],
            'services' => $this->servicePresenter->summaries($this->services->findActive($establishment)),
            'staff' => array_map(static fn (EstablishmentMember $staff): array => [
                'id' => $staff->getId(),
                'name' => $staff->getMember()?->getUser() !== null ? UserNameHelper::fullName($staff->getMember()->getUser()) : null,
                'role' => $staff->getRole()->label(),
            ], $this->staff->findForEstablishment($establishment)),
            'days' => $days,
            'defaults' => $defaults,
            'prefill' => [
                'serviceId' => $source?->getService()?->getId() ?? ($request->query->getInt('serviceId') ?: null),
                'day' => $request->query->getString('day') !== '' ? $request->query->getString('day') : $now->format('Y-m-d'),
                'time' => $source !== null ? DateHelper::time($source->getStartsAt()) : $now->modify('+1 hour')->format('H:00'),
                'discount' => $source?->getDiscount() ?? $defaults['discount'],
                'staffId' => $source?->getStaff()?->getId(),
            ],
        ]);
    }

    #[Csrf('pro')]
    #[Route('/pro/slots', name: 'pro_slot_publish', methods: ['POST'])]
    public function publish(#[MapRequestPayload(validate: false)] SlotInput $input): RedirectResponse
    {
        $establishment = $this->currentEstablishment();
        $member = $this->requirePermission(Permission::SLOTS_MANAGE, $establishment);

        if ($this->invalid($input)) {
            return $this->redirectToRoute('pro_slot_new', array_filter(['serviceId' => $input->serviceId, 'day' => $input->day]));
        }

        $slot = null;

        $this->attempt(function () use ($establishment, $member, $input, &$slot): void {
            $slot = $this->publication->publish($establishment, $member->getUser() ?? $this->currentUser(), $input);
        });

        if ($slot === null) {
            return $this->redirectToRoute('pro_slot_new', array_filter(['serviceId' => $input->serviceId, 'day' => $input->day]));
        }

        $this->addFlash('success', sprintf('Votre créneau de %s est en ligne. Il disparaîtra à %s s\'il n\'est pas réservé.', DateHelper::time($slot->getStartsAt()), DateHelper::time($slot->getVisibleUntil())));

        return $this->redirectToRoute('pro_slot_new', ['serviceId' => $input->serviceId, 'day' => $input->day]);
    }

    #[Csrf('pro')]
    #[Route('/pro/slots/{id}/withdraw', name: 'pro_slot_withdraw', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function withdraw(Request $request, int $id): RedirectResponse
    {
        $establishment = $this->currentEstablishment();
        $member = $this->requirePermission(Permission::SLOTS_MANAGE, $establishment);
        $slot = $this->slots->findOneInEstablishment($establishment, $id) ?? throw $this->createNotFoundException('Créneau introuvable.');
        $this->attempt(fn () => $this->publication->withdraw($slot, $member->getUser()), sprintf('Le créneau de %s est retiré.', DateHelper::time($slot->getStartsAt())));
        $filter = $request->request->getString('filter');

        return $this->redirectToRoute('pro_slots', isset(self::FILTERS[$filter]) && $filter !== 'all' ? ['filter' => $filter] : []);
    }
}