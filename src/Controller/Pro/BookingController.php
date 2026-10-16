<?php

namespace App\Controller\Pro;

use App\Booking\Dto\CancellationInput;
use App\Booking\Enum\BookingStatus;
use App\Booking\Presenter\BookingPresenter;
use App\Booking\Service\AttendanceService;
use App\Booking\Service\BookingService;
use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Booking;
use App\Repository\BookingRepository;
use DateTimeImmutable;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class BookingController extends AbstractProController
{
    public const TABS = [
        'upcoming' => 'À venir',
        'past' => 'Passées',
        'noshow' => 'Absences',
    ];

    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        private BookingService $bookingService,
        private AttendanceService $attendance,
        private BookingRepository $bookings,
        private BookingPresenter $presenter,
    ) {
        parent::__construct($context, $resolver);
    }

    #[Route('/pro/bookings', name: 'pro_bookings', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $establishment = $this->currentEstablishment();
        $this->requirePermission(Permission::BOOKINGS_MANAGE, $establishment);

        $tab = $request->query->getString('tab', 'upcoming');
        $tab = isset(self::TABS[$tab]) ? $tab : 'upcoming';
        $now = new DateTimeImmutable();
        $queries = [
            'upcoming' => [[BookingStatus::CONFIRMED], $now, null],
            'past' => [[BookingStatus::CONFIRMED, BookingStatus::HONORED, BookingStatus::NO_SHOW, BookingStatus::CANCELLED_BY_CUSTOMER, BookingStatus::CANCELLED_BY_ESTABLISHMENT], null, $now],
            'noshow' => [[BookingStatus::NO_SHOW], null, null],
        ];
        $tabs = [];

        foreach (self::TABS as $key => $label) {
            [$statuses, $after, $before] = $queries[$key];
            $tabs[] = ['key' => $key, 'label' => $label, 'count' => $this->bookings->countForEstablishment($establishment, $statuses, $after, $before)];
        }

        [$statuses, $after, $before] = $queries[$tab];

        return $this->render('pro/booking/index.html.twig', [
            'context' => $this->proContext(),
            'tab' => $tab,
            'tabs' => $tabs,
            'bookings' => $this->presenter->forProList($this->bookings->findForEstablishment($establishment, $statuses, $after, $before), $now),
        ]);
    }

    #[Route('/pro/bookings/{id}', name: 'pro_booking_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        return $this->render('pro/booking/show.html.twig', [
            'context' => $this->proContext(),
            'booking' => $this->presenter->forProDetail($this->findBooking($id)),
        ]);
    }

    #[Csrf('pro')]
    #[Route('/pro/bookings/{id}/honored', name: 'pro_booking_honored', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function honored(int $id): RedirectResponse
    {
        $booking = $this->findBooking($id);
        $this->attempt(fn () => $this->attendance->markHonored($booking), 'Rendez-vous marqué comme honoré.');

        return $this->redirectToRoute('pro_booking_show', ['id' => $id]);
    }

    #[Csrf('pro')]
    #[Route('/pro/bookings/{id}/no-show', name: 'pro_booking_no_show', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function noShow(int $id): RedirectResponse
    {
        $booking = $this->findBooking($id);
        $this->attempt(fn () => $this->attendance->markNoShow($booking), 'Absence enregistrée et signalée à Kréno.');

        return $this->redirectToRoute('pro_booking_show', ['id' => $id]);
    }

    #[Csrf('pro')]
    #[Route('/pro/bookings/{id}/cancel', name: 'pro_booking_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(int $id, #[MapRequestPayload(validate: false)] CancellationInput $input): RedirectResponse
    {
        $booking = $this->findBooking($id);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->bookingService->cancelByEstablishment($booking, $this->currentUser(), $input->reason), 'Rendez-vous annulé. Le client est prévenu par e-mail.');
        }

        return $this->redirectToRoute('pro_booking_show', ['id' => $id]);
    }

    private function findBooking(int $id): Booking
    {
        $establishment = $this->currentEstablishment();
        $this->requirePermission(Permission::BOOKINGS_MANAGE, $establishment);

        return $this->bookings->findOneInEstablishment($establishment, $id) ?? throw $this->createNotFoundException('Réservation introuvable.');
    }
}