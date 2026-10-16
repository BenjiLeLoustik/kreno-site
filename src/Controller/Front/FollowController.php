<?php

namespace App\Controller\Front;

use App\Establishment\Enum\FollowFlash;
use App\Establishment\Provider\FollowPageProvider;
use App\Establishment\Service\FollowService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class FollowController extends AbstractFrontController
{
    public function __construct(
        private FollowService $follows,
        private FollowPageProvider $pages,
    ) {
    }

    #[Route('/account/follows', name: 'account_follows', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('front/account/follow/index.html.twig', $this->pages->index($this->currentUser()));
    }

    #[Csrf('account')]
    #[Route(
        '/account/follows/{id}/delete',
        name: 'account_follow_delete',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function delete(int $id): RedirectResponse
    {
        $user = $this->currentUser();
        $establishment = $this->pages->establishment($id);

        $this->attempt(
            fn () => $this->follows->unfollow($establishment, $user),
            FollowFlash::UNFOLLOWED->text((string) $establishment->getName()),
        );

        return $this->redirectToRoute('account_follows');
    }
}