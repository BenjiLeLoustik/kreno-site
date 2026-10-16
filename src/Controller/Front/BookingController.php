<?php

namespace App\Controller\Front;

use App\Booking\Dto\BookingInput;
use App\Booking\Dto\CancellationInput;
use App\Booking\Enum\BookingStatus;
use App\Booking\Helper\IcsHelper;
use App\Booking\Helper\ReferenceHelper;
use App\Booking\Presenter\BookingPresenter;
use App\Booking\Service\BookingService;
use App\Entity\Booking;
use App\Entity\Establishment\EstablishmentSlot;
use App\Entity\User\User;
use App\Repository\BookingRepository;
use App\Repository\Establishment\EstablishmentSlotRepository;
use App\Shared\Exception\BusinessRuleException;
use App\User\Service\GuestAccountService;
use DateTimeImmutable;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Session\SessionManagerInterface;

class BookingController extends AbstractFrontController
{
    public const TABS = [
        'upcoming' => 'À venir',
        'past' => 'Passées',
        'cancelled' => 'Annulées',
    ];

    public function __construct(
        private BookingService $bookingService,
        private GuestAccountService $guests,
        private BookingRepository $bookings,
        private EstablishmentSlotRepository $slots,
        private BookingPresenter $presenter,
        private SessionManagerInterface $session,
    ) {
    }

    #[Csrf('booking')]
    #[Route('/slot/{id}/book', name: 'slot_book', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function book(Request $request, int $id): RedirectResponse
    {
        $slot = $this->slots->find($id);

        if (!$slot instanceof EstablishmentSlot) {
            throw $this->createNotFoundException('Ce créneau n\'existe pas.');
        }

        $input = new BookingInput();
        $input->firstName = trim($request->request->getString('firstName')) ?: null;
        $input->phone = trim($request->request->getString('phone')) ?: null;
        $input->email = trim($request->request->getString('email')) ?: null;
        $input->note = trim($request->request->getString('note')) ?: null;
        $booking = null;

        if (!$this->invalid($input)) {
            $this->attempt(function () use ($slot, $input, &$booking): void {
                $customer = $this->optionalUser();

                if ($customer === null) {
                    if ($input->email === null || $input->firstName === null || $input->phone === null) {
                        throw new BusinessRuleException('Indique ton prénom, ton téléphone et ton adresse e-mail, ou connecte-toi.');
                    }

                    $customer = $this->guests->create($input->email, $input->firstName, $input->phone);
                    $this->loginUser($customer);
                    $this->addFlash('info', sprintf('Ton compte Kréno est créé : un e-mail t\'attend à %s pour choisir ton mot de passe.', $input->email));
                }

                $booking = $this->bookingService->book($slot, $customer, $input);
            });
        }

        if (!$booking instanceof Booking) {
            $this->session->set(ShopController::OLD_BOOKING, ['slot' => $id, 'values' => ['firstName' => $input->firstName, 'phone' => $input->phone, 'email' => $input->email, 'note' => $input->note]]);

            return $this->redirectToRoute('slot_show', ['id' => $id]);
        }

        return $this->redirectToRoute('booking_confirmed', ['reference' => $booking->getReference()]);
    }

    #[Route('/booking/{reference}/confirmed', name: 'booking_confirmed', methods: ['GET'], requirements: ['reference' => ReferenceHelper::PATTERN])]
    public function confirmed(string $reference): Response
    {
        return $this->render('front/booking/confirmed.html.twig', [
            'booking' => $this->presenter->confirmation($this->findOwned($reference)),
        ]);
    }

    #[Route('/account/bookings', name: 'account_bookings', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->currentUser();
        $tab = $request->query->getString('tab', 'upcoming');
        $tab = isset(self::TABS[$tab]) ? $tab : 'upcoming';
        $now = new DateTimeImmutable();
        $lists = [
            'upcoming' => fn (): array => $this->bookings->findForCustomer($user, [BookingStatus::CONFIRMED], $now),
            'past' => fn (): array => $this->bookings->findForCustomer($user, [BookingStatus::CONFIRMED, BookingStatus::HONORED, BookingStatus::NO_SHOW], null, $now),
            'cancelled' => fn (): array => $this->bookings->findForCustomer($user, BookingRepository::CANCELLED_STATUSES),
        ];

        return $this->render('front/account/booking/index.html.twig', [
            'tab' => $tab,
            'tabs' => array_map(static fn (string $key, string $label): array => ['key' => $key, 'label' => $label, 'selected' => $key === $tab], array_keys(self::TABS), self::TABS),
            'bookings' => $this->presenter->forCustomerList($lists[$tab](), $now),
        ]);
    }

    #[Route('/account/bookings/{reference}', name: 'account_booking_show', methods: ['GET'], requirements: ['reference' => ReferenceHelper::PATTERN])]
    public function show(string $reference): Response
    {
        return $this->render('front/account/booking/show.html.twig', [
            'booking' => $this->presenter->forCustomerDetail($this->findOwned($reference)),
        ]);
    }

    #[Csrf('account')]
    #[Route('/account/bookings/{reference}/cancel', name: 'account_booking_cancel', methods: ['POST'], requirements: ['reference' => ReferenceHelper::PATTERN])]
    public function cancel(Request $request, string $reference): RedirectResponse
    {
        $booking = $this->findOwned($reference);
        $input = new CancellationInput();
        $input->reason = trim($request->request->getString('reason')) ?: null;

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->bookingService->cancelByCustomer($booking, $this->currentUser(), $input->reason), 'Réservation annulée, sans frais.');
        }

        return $this->redirectToRoute('account_booking_show', ['reference' => $reference]);
    }

    #[Route('/account/bookings/{reference}/calendar.ics', name: 'account_booking_calendar', methods: ['GET'], requirements: ['reference' => ReferenceHelper::PATTERN])]
    public function calendar(string $reference): Response
    {
        $booking = $this->findOwned($reference);
        $slot = $booking->getSlot();
        $establishment = $slot?->getEstablishment();

        if ($slot?->getStartsAt() === null || $slot->getEndsAt() === null || $booking->getStatus() !== BookingStatus::CONFIRMED) {
            throw $this->createNotFoundException('Cette réservation n\'est plus active.');
        }

        $content = IcsHelper::event(
            $booking->getReference() . '@kreno.fr',
            $slot->getStartsAt(),
            $slot->getEndsAt(),
            sprintf('%s chez %s', $slot->getService()?->getName(), $establishment?->getName()),
            $establishment !== null ? trim(sprintf('%s, %s %s', $establishment->getAddressLine1(), $establishment->getPostalCode(), $establishment->getLocality()), ', ') : null,
            sprintf('Réservation %s · paiement sur place', $booking->getReference()),
        );

        return new Response($content, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => sprintf('attachment; filename="%s.ics"', $booking->getReference()),
        ]);
    }

    private function findOwned(string $reference): Booking
    {
        $user = $this->currentUser();
        $booking = $this->bookings->findOneByReference($reference);

        if (!$booking instanceof Booking || !$booking->getCustomer() instanceof User || $booking->getCustomer() !== $user) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        return $booking;
    }
}