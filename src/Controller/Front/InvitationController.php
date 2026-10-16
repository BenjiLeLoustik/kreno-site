<?php

namespace App\Controller\Front;

use App\Company\Enum\InvitationFlash;
use App\Company\Mapper\InvitationRequestMapper;
use App\Company\Provider\InvitationPageProvider;
use App\Company\Service\InvitationAccountService;
use App\Company\Service\TeamService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class InvitationController extends AbstractFrontController
{
    public function __construct(
        private TeamService $team,
        private InvitationAccountService $accounts,
        private InvitationPageProvider $pages,
    ) {
    }

    #[Route('/invitation/{token}', name: 'pro_invitation_show', methods: ['GET'])]
    public function show(string $token): Response
    {
        $page = $this->pages->show($token, $this->optionalUser());

        return $this->render('front/invitation/show.html.twig', $page, $this->pages->status($page));
    }

    #[Csrf('invitation')]
    #[Route('/invitation/{token}', name: 'pro_invitation_accept', methods: ['POST'])]
    public function accept(Request $request, string $token): RedirectResponse
    {
        $input = InvitationRequestMapper::accept($request);

        if ($this->invalid($input)) {
            return $this->redirectToRoute('pro_invitation_show', ['token' => $token]);
        }

        $joined = $this->attempt(function () use ($token, $input): void {
            $user = $this->optionalUser();

            if ($user === null) {
                $user = $this->accounts->create($token, $input);
                $this->loginUser($user);
            }

            $this->team->accept($token, $user);
        }, InvitationFlash::WELCOME->text());

        if (!$joined) {
            return $this->redirectToRoute('pro_invitation_show', ['token' => $token]);
        }

        return $this->redirectToRoute('pro_dashboard');
    }
}