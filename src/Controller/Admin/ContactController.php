<?php

namespace App\Controller\Admin;

use App\Entity\AppContact;
use App\Repository\AppContactRepository;
use App\Support\Dto\ContactReplyInput;
use App\Support\Enum\AppContactStatus;
use App\Support\Presenter\ContactPresenter;
use App\Support\Service\ContactService;
use App\User\Enum\UserRole;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class ContactController extends AbstractAdminController
{
    public const PER_PAGE = 50;

    public function __construct(
        private AppContactRepository $contacts,
        private ContactService $service,
        private ContactPresenter $presenter,
    ) {
    }

    #[Route('/admin/contacts', name: 'admin_contacts', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->currentAdmin(UserRole::SUPPORT);

        return $this->page($request, null);
    }

    #[Route('/admin/contacts/{id}', name: 'admin_contact_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Request $request, int $id): Response
    {
        $this->currentAdmin(UserRole::SUPPORT);

        return $this->page($request, $this->findContact($id));
    }

    #[Csrf('admin_contact')]
    #[Route('/admin/contacts/{id}/reply', name: 'admin_contact_reply', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reply(Request $request, int $id): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::SUPPORT);
        $contact = $this->findContact($id);
        $input = new ContactReplyInput();
        $input->body = trim($request->request->getString('body')) ?: null;

        if (!$this->invalid($input)) {
            $replied = $this->attempt(fn () => $this->service->reply($contact, $admin, (string) $input->body), sprintf('Réponse envoyée à %s.', $contact->getEmail()));

            if ($replied && $request->request->getBoolean('close')) {
                $this->service->close($contact);
            }
        }

        return $this->redirectToRoute('admin_contact_show', ['id' => $id] + $this->query($request));
    }

    #[Csrf('admin_contact')]
    #[Route('/admin/contacts/{id}/close', name: 'admin_contact_close', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function close(Request $request, int $id): RedirectResponse
    {
        $this->currentAdmin(UserRole::SUPPORT);
        $contact = $this->findContact($id);
        $this->attempt(fn () => $this->service->close($contact), 'Demande fermée.');

        return $this->redirectToRoute('admin_contact_show', ['id' => $id] + $this->query($request));
    }

    #[Csrf('admin_contact')]
    #[Route('/admin/contacts/{id}/reopen', name: 'admin_contact_reopen', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reopen(Request $request, int $id): RedirectResponse
    {
        $this->currentAdmin(UserRole::SUPPORT);
        $contact = $this->findContact($id);
        $this->attempt(fn () => $this->service->reopen($contact), 'Demande rouverte.');

        return $this->redirectToRoute('admin_contact_show', ['id' => $id] + $this->query($request));
    }

    private function page(Request $request, ?AppContact $selected): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $status = $request->query->has('status') ? AppContactStatus::tryFrom($request->query->getString('status')) : AppContactStatus::NEW;
        $search = trim($request->query->getString('q'));
        $contacts = $this->contacts->findForAdmin($status, $search !== '' ? $search : null, self::PER_PAGE + 1, ($page - 1) * self::PER_PAGE);
        $hasNext = count($contacts) > self::PER_PAGE;
        $contacts = array_slice($contacts, 0, self::PER_PAGE);
        $selected ??= $contacts[0] ?? null;

        return $this->render('admin/contact/index.html.twig', [
            'filters' => ['q' => $search, 'status' => $status?->value ?? 'all', 'page' => $page],
            'hasNext' => $hasNext,
            'statuses' => array_map(fn (AppContactStatus $case): array => ['value' => $case->value, 'label' => $case->label(), 'count' => $this->contacts->countByStatus($case)], AppContactStatus::cases()),
            'contacts' => array_map(fn (AppContact $contact): array => $this->presenter->row($contact), $contacts),
            'selected' => $selected !== null ? $this->presenter->detail($selected) : null,
        ]);
    }

    private function query(Request $request): array
    {
        return array_filter(['status' => $request->request->getString('filterStatus', 'new'), 'q' => trim($request->request->getString('filterQ'))], static fn (string $value): bool => $value !== '');
    }

    private function findContact(int $id): AppContact
    {
        $contact = $this->contacts->find($id);

        return $contact instanceof AppContact ? $contact : throw $this->createNotFoundException('Demande introuvable.');
    }
}