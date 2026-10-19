<?php

namespace App\Controller\Front;

use App\Support\Enum\ContactFlash;
use App\Support\Mapper\ContactRequestMapper;
use App\Support\Provider\ContactPageProvider;
use App\Support\Service\ContactService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class ContactController extends AbstractFrontController
{
    public function __construct(
        private ContactService $service,
        private ContactPageProvider $pages,
    ) {
    }

    #[Route(
        '/help/contact/{token}',
        name: 'page_contact_show',
        methods: ['GET'],
        requirements: ['token' => ContactPageProvider::TOKEN_PATTERN],
    )]
    public function show(string $token): Response
    {
        return $this->render('front/contact/show.html.twig', $this->pages->show($token));
    }

    #[Csrf('contact')]
    #[Route(
        '/help/contact/{token}',
        name: 'page_contact_reply',
        methods: ['POST'],
        requirements: ['token' => ContactPageProvider::TOKEN_PATTERN],
    )]
    public function reply(Request $request, string $token): RedirectResponse
    {
        $contact = $this->pages->byToken($token);
        $input = ContactRequestMapper::reply($request);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->service->customerReply($contact, (string) $input->body),
                ContactFlash::MESSAGE_SENT->text(),
            );
        }

        return $this->redirect($this->generateUrl('page_contact_show', ['token' => $token]) . '#repondre');
    }

    #[Route('/account/messages', name: 'account_messages', methods: ['GET'])]
    public function messages(): Response
    {
        return $this->render('front/account/message/index.html.twig', $this->pages->messages($this->currentUser()));
    }
}