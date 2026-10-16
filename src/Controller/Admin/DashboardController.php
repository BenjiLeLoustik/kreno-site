<?php

namespace App\Controller\Admin;

use App\Admin\Provider\AdminDashboardProvider;
use App\User\Enum\UserRole;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class DashboardController extends AbstractAdminController
{
    public function __construct(
        private AdminDashboardProvider $dashboard,
    ) {
    }

    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $this->currentAdmin(UserRole::SUPPORT);

        return $this->render('admin/dashboard/index.html.twig', $this->dashboard->build());
    }
}