<?php

namespace App\Controller\eamPageProvider;
use App\Company\Security\PermissionResolver;
use App\Company\Service\TeamService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class TeamController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        ProContextProvider $pro,
        private TeamService $team,
        private TeamPageProvider $pages,
    ) {
        parent::__construct($context, $resolver, $pro);
    }

    #[Route('/pro/team', name: 'pro_team', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $viewer = $this->currentMember();
        $page = $this->pages->index($viewer, $this->currentCompany(), TeamRequestMapper::filter($request));

        return $this->renderPro('pro/team/index.html.twig', $page);
    }

    #[Csrf('pro')]
    #[Route('/pro/team/invitations', name: 'pro_team_invite', methods: ['POST'])]
    public function invite(
        Request $request,
        #[MapRequestPayload(validate: false)] InvitationInput $input,
    ): RedirectResponse {
        $input = TeamRequestMapper::invitation($input, $request);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->team->invite($this->currentMember(), $input),
                TeamFlash::INVITED->text($input->email),
            );
        }

        return $this->redirectToRoute('pro_team');
    }

    #[Csrf('pro')]
    #[Route(
        '/pro/team/invitations/{id}/revoke',
        name: 'pro_team_invitation_revoke',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function revoke(int $id): RedirectResponse
    {
        $invitation = $this->pages->invitation($this->currentCompany(), $id);
        $this->attempt(
            fn () => $this->team->revoke($this->currentMember(), $invitation),
            TeamFlash::INVITATION_REVOKED->text(),
        );

        return $this->redirectToRoute('pro_team');
    }

    #[Csrf('pro')]
    #[Route(
        '/pro/team/members/{id}/role',
        name: 'pro_team_member_role',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function changeRole(
        Request $request,
        int $id,
        #[MapRequestPayload(validate: false)] MemberRoleInput $input,
    ): RedirectResponse {
        $member = $this->pages->member($this->currentCompany(), $id);
        $input = TeamRequestMapper::role($input, $request);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->team->changeRole($this->currentMember(), $member, $input),
                TeamFlash::ROLE_CHANGED->text(),
            );
        }

        return $this->redirectToRoute('pro_team');
    }

    #[Csrf('pro')]
    #[Route(
        '/pro/team/members/{id}/establishments/{establishmentId}/remove',
        name: 'pro_team_member_establishment_remove',
        methods: ['POST'],
        requirements: ['id' => '\d+', 'establishmentId' => '\d+'],
    )]
    public function removeFromEstablishment(int $id, int $establishmentId): RedirectResponse
    {
        $company = $this->currentCompany();
        $establishment = $this->pages->establishment($company, $establishmentId);
        $member = $this->pages->member($company, $id);
        $this->attempt(
            fn () => $this->team->removeFromEstablishment($this->currentMember(), $member, $establishment),
            TeamFlash::ESTABLISHMENT_ACCESS_REMOVED->text($establishment->getName()),
        );

        return $this->redirectToRoute('pro_team');
    }

    #[Csrf('pro')]
    #[Route(
        '/pro/team/members/{id}/remove',
        name: 'pro_team_member_remove',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function remove(int $id): RedirectResponse
    {
        $member = $this->pages->member($this->currentCompany(), $id);
        $this->attempt(
            fn () => $this->team->remove($this->currentMember(), $member),
            TeamFlash::MEMBER_REMOVED->text(),
        );

        return $this->redirectToRoute('pro_team');
    }
}