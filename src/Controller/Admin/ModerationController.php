<?php

namespace App\Controller\Admin;

use App\Company\Dto\ModerationInput;
use App\Company\Enum\ModerationFlash;
use App\Company\Provider\AdminModerationPageProvider;
use App\Company\Service\AdminModerationService;
use App\User\Enum\UserRole;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class ModerationController extends AbstractAdminController
{
    public function __construct(
        private AdminModerationService $moderation,
        private AdminModerationPageProvider $pages,
    ) {
    }

    #[Route('/admin/pros', name: 'admin_moderation_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->currentAdmin(UserRole::MODERATOR);

        return $this->render('admin/moderation/index.html.twig', $this->pages->index());
    }

    #[Route('/admin/pros/{id}', name: 'admin_moderation_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $this->currentAdmin(UserRole::MODERATOR);

        return $this->render('admin/moderation/index.html.twig', $this->pages->index($this->pages->establishment($id)));
    }

    #[Csrf('admin_moderation')]
    #[Route(
        '/admin/pros/{id}/approve',
        name: 'admin_moderation_approve',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function approve(int $id): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $establishment = $this->pages->establishment($id);
        $this->attempt(
            fn () => $this->moderation->approve($establishment, $admin),
            ModerationFlash::APPROVED->text($establishment->getName()),
        );

        return $this->redirectToRoute('admin_moderation_show', ['id' => $id]);
    }

    #[Csrf('admin_moderation')]
    #[Route(
        '/admin/pros/{id}/reject',
        name: 'admin_moderation_reject',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function reject(int $id, #[MapRequestPayload] ModerationInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $establishment = $this->pages->establishment($id);
        $this->attempt(
            fn () => $this->moderation->reject($establishment, $input, $admin),
            ModerationFlash::REJECTED->text($establishment->getName()),
        );

        return $this->redirectToRoute('admin_moderation_show', ['id' => $id]);
    }

    #[Csrf('admin_moderation')]
    #[Route(
        '/admin/pros/{id}/request-document',
        name: 'admin_moderation_request_document',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function requestDocument(int $id, #[MapRequestPayload] ModerationInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $establishment = $this->pages->establishment($id);
        $this->attempt(
            fn () => $this->moderation->requestDocument($establishment, $input, $admin),
            ModerationFlash::DOCUMENT_REQUESTED->text($establishment->getName()),
        );

        return $this->redirectToRoute('admin_moderation_show', ['id' => $id]);
    }
}