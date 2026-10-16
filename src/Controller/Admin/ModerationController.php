<?php

namespace App\Controller\Admin;

use App\Company\Dto\ModerationInput;
use App\Company\Enum\RejectionReason;
use App\Company\Presenter\EstablishmentSummaryPresenter;
use App\Company\Service\ModerationService;
use App\Entity\Company\CompanyEstablishment;
use App\Establishment\Enum\EstablishmentStatus;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\User\Enum\UserRole;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class ModerationController extends AbstractAdminController
{
    public function __construct(
        private CompanyEstablishmentRepository $establishments,
        private ModerationService $moderation,
        private EstablishmentSummaryPresenter $presenter,
    ) {
    }

    #[Route('/admin/pros', name: 'admin_moderation_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->currentAdmin(UserRole::MODERATOR);

        return $this->page(null);
    }

    #[Route('/admin/pros/{id}', name: 'admin_moderation_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $this->currentAdmin(UserRole::MODERATOR);

        return $this->page($this->findEstablishment($id));
    }

    #[Csrf('admin_moderation')]
    #[Route('/admin/pros/{id}/approve', name: 'admin_moderation_approve', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function approve(int $id): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $establishment = $this->findEstablishment($id);

        if ($this->assertPending($establishment)) {
            $this->moderation->approve($establishment, $admin);
            $this->addFlash('success', sprintf('%s est validé et en ligne. Le pro a été prévenu par e-mail.', $establishment->getName()));
        }

        return $this->redirectToRoute('admin_moderation_show', ['id' => $id]);
    }

    #[Csrf('admin_moderation')]
    #[Route('/admin/pros/{id}/reject', name: 'admin_moderation_reject', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reject(int $id, #[MapRequestPayload] ModerationInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $establishment = $this->findEstablishment($id);

        if ($this->assertPending($establishment)) {
            $this->moderation->reject($establishment, $input, $admin);
            $this->addFlash('success', sprintf('%s est refusé. Le pro a été prévenu par e-mail.', $establishment->getName()));
        }

        return $this->redirectToRoute('admin_moderation_show', ['id' => $id]);
    }

    #[Csrf('admin_moderation')]
    #[Route('/admin/pros/{id}/request-document', name: 'admin_moderation_request_document', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function requestDocument(int $id, #[MapRequestPayload] ModerationInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $establishment = $this->findEstablishment($id);

        if ($this->assertPending($establishment)) {
            $this->moderation->requestDocument($establishment, $input, $admin);
            $this->addFlash('success', sprintf('Justificatif demandé à %s par e-mail.', $establishment->getName()));
        }

        return $this->redirectToRoute('admin_moderation_show', ['id' => $id]);
    }

    private function page(?CompanyEstablishment $selected): Response
    {
        $pending = $this->establishments->findByStatus(EstablishmentStatus::PENDING_REVIEW);
        $recent = $this->establishments->findRecentlyModerated();
        $selected ??= $pending[0] ?? $recent[0] ?? null;

        return $this->render('admin/moderation/index.html.twig', [
            'pending' => array_map($this->presenter->forModeration(...), $pending),
            'recent' => array_map($this->presenter->forModeration(...), $recent),
            'selected' => $selected !== null ? $this->presenter->forModeration($selected) : null,
            'rejectionReasons' => array_map(
                static fn (RejectionReason $reason): array => ['value' => $reason->value, 'label' => $reason->label()],
                RejectionReason::cases(),
            ),
        ]);
    }

    private function assertPending(CompanyEstablishment $establishment): bool
    {
        if ($establishment->getStatus() === EstablishmentStatus::PENDING_REVIEW) {
            return true;
        }

        $this->addFlash('error', sprintf('%s n\'est plus en attente de vérification.', $establishment->getName()));

        return false;
    }

    private function findEstablishment(int $id): CompanyEstablishment
    {
        $establishment = $this->establishments->find($id);

        return $establishment instanceof CompanyEstablishment ? $establishment : throw $this->createNotFoundException('Établissement introuvable.');
    }
}