<?php

namespace App\Controller\Pro;

use App\Company\Dto\InvitationInput;
use App\Company\Dto\MemberRoleInput;
use App\Company\Presenter\TeamPresenter;
use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Company\Service\TeamService;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyInvitation;
use App\Entity\Company\CompanyMember;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Repository\Company\CompanyInvitationRepository;
use App\Repository\Company\CompanyMemberRepository;
use DateTimeImmutable;
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
        private TeamService $team,
        private CompanyMemberRepository $members,
        private CompanyInvitationRepository $invitations,
        private CompanyEstablishmentRepository $establishments,
        private TeamPresenter $presenter,
    ) {
        parent::__construct($context, $resolver);
    }

    #[Route('/pro/team', name: 'pro_team', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $filter = $request->query->getString('establishment') ?: 'all';
        $viewer = $this->currentMember();
        $company = $this->currentCompany();
        $members = $company->getMembers()->toArray();

        if ($filter !== 'all') {
            $members = array_values(array_filter($members, static function (CompanyMember $member) use ($filter): bool {
                if ($member->getRole() !== null) {
                    return true;
                }

                foreach ($member->getEstablishmentRoles() as $role) {
                    if ((string) $role->getEstablishment()?->getId() === $filter) {
                        return true;
                    }
                }

                return false;
            }));
        }

        $manageable = array_values(array_filter(
            $this->context->getEstablishments($viewer),
            fn (CompanyEstablishment $establishment): bool => $this->resolver->memberCan($viewer, Permission::MEMBERS_MANAGE, $establishment),
        ));

        if ($manageable === [] && !$this->resolver->isCompanyAdmin($viewer)) {
            throw $this->createAccessDeniedException('Votre rôle ne permet pas de gérer l\'équipe.');
        }

        return $this->render('pro/team/index.html.twig', [
            'context' => $this->proContext(),
            'filter' => $filter,
            'members' => array_map(fn (CompanyMember $member): array => $this->presenter->member($member, $viewer), $members),
            'invitations' => array_map(fn (CompanyInvitation $invitation): array => $this->presenter->invitation($invitation), $this->invitations->findPendingForCompany($company, new DateTimeImmutable())),
            'canInviteAdmins' => $this->resolver->isCompanyAdmin($viewer),
            'establishments' => array_map(static fn (CompanyEstablishment $establishment): array => ['id' => $establishment->getId(), 'name' => $establishment->getName()], $this->context->getEstablishments($viewer)),
            'manageableEstablishments' => array_map(static fn (CompanyEstablishment $establishment): array => ['id' => $establishment->getId(), 'name' => $establishment->getName()], $manageable),
            'roleMatrix' => $this->presenter->roleMatrix(),
        ]);
    }

    #[Csrf('pro')]
    #[Route('/pro/team/invitations', name: 'pro_team_invite', methods: ['POST'])]
    public function invite(Request $request, #[MapRequestPayload(validate: false)] InvitationInput $input): RedirectResponse
    {
        $input->canManageBookings = $request->request->getBoolean('canManageBookings');
        $input->establishmentId = $input->role === 'admin' ? null : ($request->request->getInt('establishmentId') ?: null);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->team->invite($this->currentMember(), $input), sprintf('Invitation envoyée à %s.', $input->email));
        }

        return $this->redirectToRoute('pro_team');
    }

    #[Csrf('pro')]
    #[Route('/pro/team/invitations/{id}/revoke', name: 'pro_team_invitation_revoke', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function revoke(int $id): RedirectResponse
    {
        $invitation = $this->invitations->find($id);

        if (!$invitation instanceof CompanyInvitation || $invitation->getCompany() !== $this->currentCompany()) {
            throw $this->createNotFoundException('Invitation introuvable.');
        }

        $this->attempt(fn () => $this->team->revoke($this->currentMember(), $invitation), 'Invitation annulée.');

        return $this->redirectToRoute('pro_team');
    }

    #[Csrf('pro')]
    #[Route('/pro/team/members/{id}/role', name: 'pro_team_member_role', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function changeRole(Request $request, int $id, #[MapRequestPayload(validate: false)] MemberRoleInput $input): RedirectResponse
    {
        $member = $this->findMember($id);
        $input->canManageBookings = $request->request->getBoolean('canManageBookings');
        $input->establishmentId = $input->role === 'admin' ? null : ($request->request->getInt('establishmentId') ?: null);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->team->changeRole($this->currentMember(), $member, $input), 'Rôle modifié.');
        }

        return $this->redirectToRoute('pro_team');
    }

    #[Csrf('pro')]
    #[Route('/pro/team/members/{id}/establishments/{establishmentId}/remove', name: 'pro_team_member_establishment_remove', methods: ['POST'], requirements: ['id' => '\d+', 'establishmentId' => '\d+'])]
    public function removeFromEstablishment(int $id, int $establishmentId): RedirectResponse
    {
        $establishment = $this->establishments->findOneInCompany($this->currentCompany(), $establishmentId) ?? throw $this->createNotFoundException('Établissement introuvable.');
        $member = $this->findMember($id);
        $this->attempt(fn () => $this->team->removeFromEstablishment($this->currentMember(), $member, $establishment), sprintf('Accès à %s retiré.', $establishment->getName()));

        return $this->redirectToRoute('pro_team');
    }

    #[Csrf('pro')]
    #[Route('/pro/team/members/{id}/remove', name: 'pro_team_member_remove', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function remove(int $id): RedirectResponse
    {
        $member = $this->findMember($id);
        $this->attempt(fn () => $this->team->remove($this->currentMember(), $member), 'Membre retiré de l\'entreprise.');

        return $this->redirectToRoute('pro_team');
    }

    private function findMember(int $id): CompanyMember
    {
        return $this->members->findOneInCompany($this->currentCompany(), $id) ?? throw $this->createNotFoundException('Membre introuvable.');
    }
}