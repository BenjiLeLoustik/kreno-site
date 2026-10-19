<?php

namespace App\Controller\Front;

use App\Booking\Helper\ReferenceHelper;
use App\Review\Dto\ReviewInput;
use App\Review\Enum\ReviewFlash;
use App\Review\Provider\CustomerReviewPageProvider;
use App\Review\Service\ReviewService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class ReviewController extends AbstractFrontController
{
    public function __construct(
        private ReviewService $reviews,
        private CustomerReviewPageProvider $pages,
    ) {
    }

    #[Route('/account/reviews', name: 'account_reviews', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('front/account/review/index.html.twig', $this->pages->index($this->currentUser()));
    }

    #[Route(
        '/account/reviews/{reference}',
        name: 'account_review_new',
        methods: ['GET'],
        requirements: ['reference' => ReferenceHelper::PATTERN],
    )]
    public function new(string $reference): Response
    {
        $user = $this->currentUser();
        $page = null;

        $this->attempt(function () use ($reference, $user, &$page): void {
            $page = $this->pages->form($reference, $user);
        });

        if ($page === null) {
            return $this->redirectToRoute('account_reviews');
        }

        return $this->render('front/account/review/new.html.twig', $page);
    }

    #[Csrf('account')]
    #[Route(
        '/account/reviews/{reference}',
        name: 'account_review_create',
        methods: ['POST'],
        requirements: ['reference' => ReferenceHelper::PATTERN],
    )]
    public function create(
        string $reference,
        #[MapRequestPayload(validate: false)] ReviewInput $input,
    ): RedirectResponse {
        $user = $this->currentUser();
        $booking = $this->pages->booking($reference, $user);

        if ($this->invalid($input)) {
            return $this->redirectToRoute('account_review_new', ['reference' => $reference]);
        }

        $this->attempt(fn () => $this->reviews->create($booking, $user, $input), ReviewFlash::CREATED->text());

        return $this->redirectToRoute('account_reviews');
    }
}