<?php

namespace App\Controller\Admin;

use App\Booking\Mapper\AdminBookingRequestMapper;
use App\Booking\Provider\AdminBookingPageProvider;
use App\User\Enum\UserRole;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class BookingController extends AbstractAdminController
{
    public function __construct(
        private AdminBookingPageProvider $pages,
    ) {
    }

    #[Route('/admin/bookings', name: 'admin_bookings', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->currentAdmin(UserRole::SUPPORT);
        $page = $this->pages->index(
            AdminBookingRequestMapper::tab($request),
            AdminBookingRequestMapper::search($request),
            AdminBookingRequestMapper::page($request),
        );

        return $this->render('admin/booking/index.html.twig', $page);
    }

    #[Route('/admin/bookings/{id}', name: 'admin_booking_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $this->currentAdmin(UserRole::SUPPORT);

        return $this->render('admin/booking/show.html.twig', $this->pages->show($id));
    }
}