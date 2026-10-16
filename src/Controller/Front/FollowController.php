<?php

namespace App\Controller\Front;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentFollower;
use App\Establishment\Presenter\EstablishmentPagePresenter;
use App\Establishment\Service\FollowService;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Repository\Establishment\EstablishmentFollowerRepository;
use DateTimeImmutable;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class FollowController extends AbstractFrontController
{
    public function __construct(
        private EstablishmentFollowerRepository $followers,
        private CompanyEstablishmentRepository $establishments,
        private FollowService $follows,
        private EstablishmentPagePresenter $pagePresenter,
    ) {
    }

    #[Route('/account/follows', name: 'account_follows', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->currentUser();
        $now = new DateTimeImmutable();
        $items = [];

        foreach ($this->followers->findByUser($user) as $follower) {
            if ($follower instanceof EstablishmentFollower && $follower->getEstablishment() !== null) {
                $items[] = $this->pagePresenter->card($follower->getEstablishment(), $now);
            }
        }

        return $this->render('front/account/follow/index.html.twig', ['follows' => $items]);
    }

    #[Csrf('account')]
    #[Route('/account/follows/{id}/delete', name: 'account_follow_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id): RedirectResponse
    {
        $user = $this->currentUser();
        /** @var CompanyEstablishment $establishment */
        $establishment = $this->establishments->find($id) ?? throw $this->createNotFoundException('Établissement introuvable.');
        $this->attempt(fn () => $this->follows->unfollow($establishment, $user), sprintf('Vous ne suivez plus %s.', $establishment->getName()));

        return $this->redirectToRoute('account_follows');
    }
}