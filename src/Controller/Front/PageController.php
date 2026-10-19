<?php

namespace App\Controller\Front;

use App\Support\Mapper\ContactRequestMapper;
use App\Support\Service\ContactService;
use App\Website\Enum\LegalDocument;
use App\Website\Provider\SitePageProvider;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class PageController extends AbstractFrontController
{
    public function __construct(
        private ContactService $contact,
        private SitePageProvider $pages,
    ) {
    }

    #[Route('/how-it-works', name: 'page_how_it_works', methods: ['GET'])]
    public function howItWorks(): Response
    {
        return $this->render('front/page/how_it_works.html.twig', $this->pages->howItWorks());
    }

    #[Route('/help', name: 'page_help', methods: ['GET'])]
    public function help(): Response
    {
        return $this->render('front/page/help.html.twig', $this->pages->help($this->optionalUser()));
    }

    #[Csrf('help')]
    #[Route('/help', name: 'page_help_submit', methods: ['POST'])]
    public function helpSubmit(Request $request): RedirectResponse
    {
        $input = ContactRequestMapper::contact($request);
        $contact = null;

        if (!$this->invalid($input)) {
            $this->attempt(function () use ($input, &$contact): void {
                $contact = $this->contact->send($input, $this->optionalUser());
            });
        }

        $this->pages->rememberHelp($input, $contact);

        return $this->redirect($this->generateUrl('page_help') . '#contact');
    }

    #[Route(
        '/legal/{document}',
        name: 'page_legal',
        methods: ['GET'],
        requirements: ['document' => LegalDocument::PATTERN],
        defaults: ['document' => 'cgu'],
    )]
    public function legal(string $document = 'cgu'): Response
    {
        return $this->render('front/page/legal.html.twig', $this->pages->legal($document));
    }
}