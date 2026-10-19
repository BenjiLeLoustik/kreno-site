<?php

namespace App\Controller\Admin;

use App\User\Dto\StaffInviteInput;
use App\User\Dto\StaffRoleInput;
use App\User\Enum\AdminTeamFlash;
use App\User\Mapper\StaffRequestMapper;
use App\User\Provider\AdminTeamPageProvider;
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
        private AdminTeamPageProvider $pages,
    ) {
    }

    #[Route('/admin/team', name: 'admin_team', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/team/index.html.twig', $this->pages->index($this->currentAdmin()));
    }

    #[Csrf('admin_team')]
    #[Route('/admin/team', name: 'admin_team_invite', methods: ['POST'])]
    public function invite(#[MapRequestPayload(validate: false)] StaffInviteInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->staff->invite($input, $admin),
                AdminTeamFlash::INVITED->text($input->email),
            );
        }

        return $this->redirectToRoute('admin_team');
    }

    #[Csrf('admin_team')]
    #[Route('/admin/team/{id}/role', name: 'admin_team_role', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function role(int $id, #[MapRequestPayload(validate: false)] StaffRoleInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $member = $this->pages->member($id);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->staff->changeRole($member, StaffRequestMapper::role($input), $admin),
                AdminTeamFlash::ROLE_CHANGED->text(),
            );
        }

        return $this->redirectToRoute('admin_team');
    }

    #[Csrf('admin_team')]
    #[Route('/admin/team/{id}/revoke', name: 'admin_team_revoke', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function revoke(int $id): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $member = $this->pages->member($id);
        $this->attempt(fn () => $this->staff->revoke($member, $admin), AdminTeamFlash::REVOKED->text());

        return $this->redirectToRoute('admin_team');
    }
}