<?php

namespace App\Controller\Admin;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use App\User\Dto\StaffInviteInput;
use App\User\Dto\StaffRoleInput;
use App\User\Enum\UserRole;
use App\User\Helper\StaffRoleHelper;
use App\User\Presenter\StaffPresenter;
use App\User\Service\StaffService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class TeamController extends AbstractAdminController
{
    public function __construct(
        private StaffService $staff,
        private UserRepository $users,
        private StaffPresenter $presenter,
    ) {
    }

    #[Route('/admin/team', name: 'admin_team', methods: ['GET'])]
    public function index(): Response
    {
        $admin = $this->currentAdmin();

        return $this->render('admin/team/index.html.twig', [
            'members' => $this->presenter->presentAll($this->staff->members(), $admin),
            'roles' => StaffRoleHelper::choices(),
        ]);
    }

    #[Csrf('admin_team')]
    #[Route('/admin/team', name: 'admin_team_invite', methods: ['POST'])]
    public function invite(#[MapRequestPayload(validate: false)] StaffInviteInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->staff->invite($input, $admin), sprintf('Invitation envoyée à %s.', $input->email));
        }

        return $this->redirectToRoute('admin_team');
    }

    #[Csrf('admin_team')]
    #[Route('/admin/team/{id}/role', name: 'admin_team_role', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function role(int $id, #[MapRequestPayload(validate: false)] StaffRoleInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $member = $this->findUser($id);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->staff->changeRole($member, UserRole::from((string) $input->role), $admin), 'Rôle modifié.');
        }

        return $this->redirectToRoute('admin_team');
    }

    #[Csrf('admin_team')]
    #[Route('/admin/team/{id}/revoke', name: 'admin_team_revoke', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function revoke(int $id): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $member = $this->findUser($id);
        $this->attempt(fn () => $this->staff->revoke($member, $admin), 'Accès retiré.');

        return $this->redirectToRoute('admin_team');
    }

    private function findUser(int $id): User
    {
        $user = $this->users->find($id);

        return $user instanceof User ? $user : throw $this->createNotFoundException('Membre introuvable.');
    }
}