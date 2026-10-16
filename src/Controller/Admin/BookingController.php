<?php

namespace App\Controller\Admin;

use App\Booking\Enum\BookingStatus;
use App\Booking\Presenter\BookingPresenter;
use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\User\Enum\UserRole;
use DateTimeImmutable;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class BookingController extends AbstractAdminController
{
    public const TABS = [
        'all' => ['label' => 'Toutes', 'statuses' => [BookingStatus::CONFIRMED, BookingStatus::HONORED, BookingStatus::NO_SHOW, BookingStatus::CANCELLED_BY_CUSTOMER, BookingStatus::CANCELLED_BY_ESTABLISHMENT]],
        'coming' => ['label' => 'À venir', 'statuses' => [BookingStatus::CONFIRMED]],
        'done' => ['label' => 'Honorées', 'statuses' => [BookingStatus::HONORED]],
        'cancelled' => ['label' => 'Annulées', 'statuses' => [BookingStatus::CANCELLED_BY_CUSTOMER, BookingStatus::CANCELLED_BY_ESTABLISHMENT]],
        'noshow' => ['label' => 'Absences', 'statuses' => [BookingStatus::NO_SHOW]],
    ];

    public const PAGE_SIZE = 50;

    public function __construct(
        private BookingRepository $bookings,
        private BookingPresenter $presenter,
    ) {
    }

    #[Route('/admin/bookings', name: 'admin_bookings', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->currentAdmin(UserRole::SUPPORT);

        $tab = $request->query->getString('tab', 'all');
        $tab = isset(self::TABS[$tab]) ? $tab : 'all';
        $search = trim($request->query->getString('q'));
        $page = max(1, $request->query->getInt('page', 1));
        $now = new DateTimeImmutable();
        $today = $now->setTime(0, 0);
        $tomorrow = $today->modify('+1 day');
        $created = $this->bookings->countCreatedBetween($today, $tomorrow);
        $cancelled = $this->bookings->countCancelledBetween($today, $tomorrow);
        $noShows = $this->bookings->countNoShowsBetween($today, $tomorrow);
        $bookings = $this->bookings->findForAdmin(self::TABS[$tab]['statuses'], $search !== '' ? $search : null, self::PAGE_SIZE + 1, ($page - 1) * self::PAGE_SIZE);

        return $this->render('admin/booking/index.html.twig', [
            'tab' => $tab,
            'tabs' => array_map(static fn (string $key, array $definition): array => ['key' => $key, 'label' => $definition['label']], array_keys(self::TABS), self::TABS),
            'q' => $search,
            'page' => $page,
            'hasNext' => count($bookings) > self::PAGE_SIZE,
            'today' => sprintf('Aujourd\'hui : %d réservation%s · %d annulation%s · %d absence%s', $created, $created > 1 ? 's' : '', $cancelled, $cancelled > 1 ? 's' : '', $noShows, $noShows > 1 ? 's' : ''),
            'bookings' => $this->presenter->forAdminList(array_slice($bookings, 0, self::PAGE_SIZE), $now),
        ]);
    }

    #[Route('/admin/bookings/{id}', name: 'admin_booking_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $this->currentAdmin(UserRole::SUPPORT);
        $booking = $this->bookings->find($id);

        if (!$booking instanceof Booking) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        return $this->render('admin/booking/show.html.twig', ['booking' => $this->presenter->forAdminDetail($booking)]);
    }
}