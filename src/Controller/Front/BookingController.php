<?php

namespace App\Controller\Front;

use App\Booking\Enum\BookingFlash;
use App\Booking\Helper\ReferenceHelper;
use App\Booking\Mapper\BookingRequestMapper;
use App\Booking\Presenter\BookingPresenter;
use App\Booking\Provider\CustomerBookingsProvider;
use App\Booking\Service\BookingCalendarService;
use App\Booking\Service\BookingService;
use App\Booking\Service\GuestBookingService;
use App\Entity\Booking;
use App\Establishment\Provider\SlotPageProvider;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class BookingController extends AbstractFrontController
{
    public function __construct(
        private BookingService $bookingService,
        private GuestBookingService $guestBookings,
        private CustomerBookingsProvider $customerBookings,
        private BookingCalendarService $calendar,
        private BookingPresenter $presenter,
        private SlotPageProvider $slotPages,
    ) {
    }

    #[Csrf('booking')]
    #[Route('/slot/{id}/book', name: 'slot_book', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function book(Request $request, int $id): RedirectResponse
    {
        $slot = $this->guestBookings->slot($id);
        $input = BookingRequestMapper::booking($request);
        $booking = null;

        if (!$this->invalid($input)) {
            $this->attempt(function () use ($slot, $input, &$booking): void {
                $customer = $this->optionalUser();

                if ($customer === null) {
                    $customer = $this->guestBookings->guest($input);
                    $this->loginUser($customer);
                    $this->addFlash('info', BookingFlash::GUEST_ACCOUNT_CREATED->text((string) $input->email));
                }

                $booking = $this->bookingService->book($slot, $customer, $input);
            });
        }

        if (!$booking instanceof Booking) {
            $this->slotPages->remember(BookingRequestMapper::oldBooking($id, $input));

            return $this->redirectToRoute('slot_show', ['id' => $id]);
        }

        return $this->redirectToRoute('booking_confirmed', ['reference' => $booking->getReference()]);
    }

    #[Route(
        '/booking/{reference}/confirmed',
        name: 'booking_confirmed',
        methods: ['GET'],
        requirements: ['reference' => ReferenceHelper::PATTERN],
    )]
    public function confirmed(string $reference): Response
    {
        $booking = $this->customerBookings->owned($reference, $this->currentUser());

        return $this->render('front/booking/confirmed.html.twig', [
            'booking' => $this->presenter->confirmation($booking),
        ]);
    }

    #[Route('/account/bookings', name: 'account_bookings', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $this->customerBookings->index($this->currentUser(), $request->query->getString('tab'));

        return $this->render('front/account/booking/index.html.twig', $page);
    }

    #[Route(
        '/account/bookings/{reference}',
        name: 'account_booking_show',
        methods: ['GET'],
        requirements: ['reference' => ReferenceHelper::PATTERN],
    )]
    public function show(string $reference): Response
    {
        $booking = $this->customerBookings->owned($reference, $this->currentUser());

        return $this->render('front/account/booking/show.html.twig', [
            'booking' => $this->presenter->forCustomerDetail($booking),
        ]);
    }

    #[Csrf('account')]
    #[Route(
        '/account/bookings/{reference}/cancel',
        name: 'account_booking_cancel',
        methods: ['POST'],
        requirements: ['reference' => ReferenceHelper::PATTERN],
    )]
    public function cancel(Request $request, string $reference): RedirectResponse
    {
        $user = $this->currentUser();
        $booking = $this->customerBookings->owned($reference, $user);
        $input = BookingRequestMapper::cancellation($request);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->bookingService->cancelByCustomer($booking, $user, $input->reason),
                BookingFlash::CANCELLED->text(),
            );
        }

        return $this->redirectToRoute('account_booking_show', ['reference' => $reference]);
    }

    #[Route(
        '/account/bookings/{reference}/calendar.ics',
        name: 'account_booking_calendar',
        methods: ['GET'],
        requirements: ['reference' => ReferenceHelper::PATTERN],
    )]
    public function calendar(string $reference): Response
    {
        $booking = $this->customerBookings->owned($reference, $this->currentUser());

        return new Response($this->calendar->ics($booking), 200, [
            'Content-Type' => BookingCalendarService::CONTENT_TYPE,
            'Content-Disposition' => sprintf('attachment; filename="%s"', $this->calendar->fileName($booking)),
        ]);
    }
}