<?php

namespace App\Controller\Front;

use App\Entity\AppContact;
use App\Repository\AppContactRepository;
use App\Support\Dto\ContactReplyInput;
use App\Support\Presenter\ContactPresenter;
use App\Support\Service\ContactService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class ContactController extends AbstractFrontController
{
    public const TOKEN_PATTERN = '[A-Za-z0-9_\-]{20,64}';

    public function __construct(
        private AppContactRepository $contacts,
        private ContactService $service,
        private ContactPresenter $presenter,
    ) {
    }

    #[Route('/help/contact/{token}', name: 'page_contact_show', methods: ['GET'], requirements: ['token' => self::TOKEN_PATTERN])]
    public function show(string $token): Response
    {
        return $this->render('front/contact/show.html.twig', [
            'contact' => $this->presenter->detail($this->findContact($token), false),
        ]);
    }

    #[Csrf('contact')]
    #[Route('/help/contact/{token}', name: 'page_contact_reply', methods: ['POST'], requirements: ['token' => self::TOKEN_PATTERN])]
    public function reply(Request $request, string $token): RedirectResponse
    {
        $contact = $this->findContact($token);
        $input = new ContactReplyInput();
        $input->body = trim($request->request->getString('body')) ?: null;

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->service->customerReply($contact, (string) $input->body), 'Message envoyé. Nous vous répondons par e-mail.');
        }

        return $this->redirect($this->generateUrl('page_contact_show', ['token' => $token]) . '#repondre');
    }

    #[Route('/account/messages', name: 'account_messages', methods: ['GET'])]
    public function messages(): Response
    {
        return $this->render('front/account/message/index.html.twig', [
            'contacts' => array_map(fn (AppContact $contact): array => $this->presenter->row($contact), $this->contacts->findForUser($this->currentUser())),
        ]);
    }

    private function findContact(string $token): AppContact
    {
        return $this->contacts->findOneByToken($token) ?? throw $this->createNotFoundException('Cette conversation n\'existe pas.');
    }
}