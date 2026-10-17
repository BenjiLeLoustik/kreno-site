<?php

namespace App\Controller\Admin;

use App\Review\Dto\ReviewHideInput;
use App\Review\Enum\ReviewFlash;
use App\Review\Mapper\ReviewRequestMapper;
use App\Review\Provider\AdminReviewPageProvider;
use App\Review\Service\AdminReviewService;
use App\User\Enum\UserRole;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class ReviewController extends AbstractAdminController
{
    public function __construct(
        private AdminReviewService $moderation,
        private AdminReviewPageProvider $pages,
    ) {
    }

    #[Route('/admin/reviews', name: 'admin_reviews', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->currentAdmin(UserRole::MODERATOR);

        return $this->render('admin/review/index.html.twig', $this->pages->index(
            ReviewRequestMapper::adminFilter($request),
        ));
    }

    #[Csrf('admin_review')]
    #[Route('/admin/reviews/{id}/hide', name: 'admin_review_hide', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function hide(
        Request $request,
        int $id,
        #[MapRequestPayload(validate: false)] ReviewHideInput $input,
    ): RedirectResponse {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $review = $this->pages->review($id);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->moderation->hide($review, $admin, (string) $input->reason),
                ReviewFlash::HIDDEN->text(),
            );
        }

        return $this->redirectToRoute('admin_reviews', ReviewRequestMapper::adminReturn($request));
    }

    #[Csrf('admin_review')]
    #[Route(
        '/admin/reviews/{id}/publish',
        name: 'admin_review_publish',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function publish(Request $request, int $id): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $review = $this->pages->review($id);
        $this->attempt(fn () => $this->moderation->publish($review, $admin), ReviewFlash::PUBLISHED->text());

        return $this->redirectToRoute('admin_reviews', ReviewRequestMapper::adminReturn($request));
    }
}