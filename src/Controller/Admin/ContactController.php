<?php

namespace App\Controller\Admin;

use App\Shared\Mapper\AdminQueryMapper;
use App\Support\Enum\AdminContactFlash;
use App\Support\Mapper\AdminContactRequestMapper;
use App\Support\Mapper\ContactRequestMapper;
use App\Support\Provider\AdminContactPageProvider;
use App\Support\Service\AdminContactService;
use App\Support\Service\ContactService;
use App\User\Enum\UserRole;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class ContactController extends AbstractAdminController
{
    public function __construct(
        private ContactService $service,
        private AdminContactService $replies,
        private AdminContactPageProvider $pages,
    ) {
    }

    #[Route('/admin/contacts', name: 'admin_contacts', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->currentAdmin(UserRole::SUPPORT);
        $page = $this->pages->index(
            AdminQueryMapper::page($request),
            AdminContactRequestMapper::filterStatus($request),
            AdminQueryMapper::search($request),
        );

        return $this->render('admin/contact/index.html.twig', $page);
    }

    #[Route('/admin/contacts/{id}', name: 'admin_contact_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Request $request, int $id): Response
    {
        $this->currentAdmin(UserRole::SUPPORT);
        $page = $this->pages->index(
            AdminQueryMapper::page($request),
            AdminContactRequestMapper::filterStatus($request),
            AdminQueryMapper::search($request),
            $this->pages->contact($id),
        );

        return $this->render('admin/contact/index.html.twig', $page);
    }

    #[Csrf('admin_contact')]
    #[Route(
        '/admin/contacts/{id}/reply',
        name: 'admin_contact_reply',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function reply(Request $request, int $id): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::SUPPORT);
        $contact = $this->pages->contact($id);
        $input = ContactRequestMapper::reply($request);
        $close = AdminContactRequestMapper::closeAfterReply($request);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->replies->reply($contact, $admin, (string) $input->body, $close),
                AdminContactFlash::REPLIED->text($contact->getEmail()),
            );
        }

        $back = ['id' => $id] + AdminContactRequestMapper::returnQuery($request);

        return $this->redirectToRoute('admin_contact_show', $back);
    }

    #[Csrf('admin_contact')]
    #[Route(
        '/admin/contacts/{id}/close',
        name: 'admin_contact_close',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function close(Request $request, int $id): RedirectResponse
    {
        $this->currentAdmin(UserRole::SUPPORT);
        $contact = $this->pages->contact($id);
        $this->attempt(fn () => $this->service->close($contact), AdminContactFlash::CLOSED->text());

        $back = ['id' => $id] + AdminContactRequestMapper::returnQuery($request);

        return $this->redirectToRoute('admin_contact_show', $back);
    }

    #[Csrf('admin_contact')]
    #[Route(
        '/admin/contacts/{id}/reopen',
        name: 'admin_contact_reopen',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function reopen(Request $request, int $id): RedirectResponse
    {
        $this->currentAdmin(UserRole::SUPPORT);
        $contact = $this->pages->contact($id);
        $this->attempt(fn () => $this->service->reopen($contact), AdminContactFlash::REOPENED->text());

        $back = ['id' => $id] + AdminContactRequestMapper::returnQuery($request);

        return $this->redirectToRoute('admin_contact_show', $back);
    }
}