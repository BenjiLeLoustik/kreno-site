<?php

namespace App\Controller\Admin;

use App\Shared\Mapper\AdminQueryMapper;
use App\User\Dto\SanctionInput;
use App\User\Enum\AdminUserFlash;
use App\User\Enum\UserRole;
use App\User\Mapper\AdminUserRequestMapper;
use App\User\Provider\AdminUserPageProvider;
use App\User\Service\AdminSanctionService;
use App\User\Service\SanctionService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class UserController extends AbstractAdminController
{
    public function __construct(
        private SanctionService $sanctions,
        private AdminSanctionService $adminSanctions,
        private AdminUserPageProvider $pages,
    ) {
    }

    #[Route('/admin/users', name: 'admin_user_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->currentAdmin(UserRole::MODERATOR);
        $page = $this->pages->index(
            AdminQueryMapper::page($request),
            AdminUserRequestMapper::status($request),
            AdminQueryMapper::search($request),
        );

        return $this->render('admin/user/index.html.twig', $page);
    }

    #[Route('/admin/users/{id}', name: 'admin_user_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Request $request, int $id): Response
    {
        $this->currentAdmin(UserRole::MODERATOR);
        $page = $this->pages->index(
            AdminQueryMapper::page($request),
            AdminUserRequestMapper::status($request),
            AdminQueryMapper::search($request),
            $this->pages->user($id),
        );

        return $this->render('admin/user/index.html.twig', $page);
    }

    #[Csrf('admin_user')]
    #[Route('/admin/users/{id}/suspend', name: 'admin_user_suspend', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function suspend(int $id, #[MapRequestPayload(validate: false)] SanctionInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $user = $this->pages->user($id);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->adminSanctions->suspend($user, $input, $admin),
                AdminUserFlash::SUSPENDED->text($this->adminSanctions->days($input)),
            );
        }

        return $this->redirectToRoute('admin_user_show', ['id' => $id]);
    }

    #[Csrf('admin_user')]
    #[Route('/admin/users/{id}/lift', name: 'admin_user_lift', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function lift(int $id): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $user = $this->pages->user($id);
        $this->attempt(fn () => $this->sanctions->lift($user, $admin), AdminUserFlash::LIFTED->text());

        return $this->redirectToRoute('admin_user_show', ['id' => $id]);
    }

    #[Csrf('admin_user')]
    #[Route('/admin/users/{id}/ban', name: 'admin_user_ban', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function ban(int $id, #[MapRequestPayload(validate: false)] SanctionInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $user = $this->pages->user($id);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->adminSanctions->ban($user, $input, $admin),
                AdminUserFlash::BANNED->text(),
            );
        }

        return $this->redirectToRoute('admin_user_show', ['id' => $id]);
    }
}