<?php

namespace App\Controller\Pro;

use App\Booking\Enum\ProBookingFlash;
use App\Booking\Mapper\BookingRequestMapper;
use App\Booking\Provider\ProBookingsProvider;
use App\Booking\Service\AttendanceService;
use App\Booking\Service\BookingService;
use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Provider\ProContextProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class BookingController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        ProContextProvider $pro,
        private BookingService $bookingService,
        private AttendanceService $attendance,
        private ProBookingsProvider $bookings,
    ) {
        parent::__construct($context, $resolver, $pro);
    }

    #[Route('/pro/bookings', name: 'pro_bookings', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $establishment = $this->permittedEstablishment(Permission::BOOKINGS_MANAGE);
        $page = $this->bookings->index($establishment, $request->query->getString('tab'));

        return $this->renderPro('pro/booking/index.html.twig', $page);
    }

    #[Route('/pro/bookings/{id}', name: 'pro_booking_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $establishment = $this->permittedEstablishment(Permission::BOOKINGS_MANAGE);

        return $this->renderPro('pro/booking/show.html.twig', $this->bookings->show($establishment, $id));
    }

    #[Csrf('pro')]
    #[Route(
        '/pro/bookings/{id}/honored',
        name: 'pro_booking_honored',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function honored(int $id): RedirectResponse
    {
        $booking = $this->bookings->owned($this->permittedEstablishment(Permission::BOOKINGS_MANAGE), $id);
        $this->attempt(fn () => $this->attendance->markHonored($booking), ProBookingFlash::HONORED->text());

        return $this->redirectToRoute('pro_booking_show', ['id' => $id]);
    }

    #[Csrf('pro')]
    #[Route(
        '/pro/bookings/{id}/no-show',
        name: 'pro_booking_no_show',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function noShow(int $id): RedirectResponse
    {
        $booking = $this->bookings->owned($this->permittedEstablishment(Permission::BOOKINGS_MANAGE), $id);
        $this->attempt(fn () => $this->attendance->markNoShow($booking), ProBookingFlash::NO_SHOW->text());

        return $this->redirectToRoute('pro_booking_show', ['id' => $id]);
    }

    #[Csrf('pro')]
    #[Route('/pro/bookings/{id}/cancel', name: 'pro_booking_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(Request $request, int $id): RedirectResponse
    {
        $booking = $this->bookings->owned($this->permittedEstablishment(Permission::BOOKINGS_MANAGE), $id);
        $input = BookingRequestMapper::cancellation($request);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->bookingService->cancelByEstablishment($booking, $this->currentUser(), $input->reason),
                ProBookingFlash::CANCELLED->text(),
            );
        }

        return $this->redirectToRoute('pro_booking_show', ['id' => $id]);
    }
}