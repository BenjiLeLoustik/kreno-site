<?php

namespace App\Controller\Pro;

use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Provider\ProContextProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Review\Dto\ReviewReplyInput;
use App\Review\Dto\ReviewReportInput;
use App\Review\Enum\ReviewFlash;
use App\Review\Mapper\ReviewRequestMapper;
use App\Review\Provider\ProReviewPageProvider;
use App\Review\Service\ProReviewService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class ReviewController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        ProContextProvider $pro,
        private ProReviewService $reviewService,
        private ProReviewPageProvider $reviews,
    ) {
        parent::__construct($context, $resolver, $pro);
    }

    #[Route('/pro/reviews', name: 'pro_reviews', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $this->reviews->index(
            $this->currentMember(),
            $this->currentEstablishment(),
            ReviewRequestMapper::proTab($request),
        );

        return $this->renderPro('pro/review/index.html.twig', $page);
    }

    #[Csrf('pro')]
    #[Route('/pro/reviews/{id}/reply', name: 'pro_review_reply', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reply(
        Request $request,
        int $id,
        #[MapRequestPayload(validate: false)] ReviewReplyInput $input,
    ): RedirectResponse {
        $establishment = $this->permittedEstablishment(Permission::ESTABLISHMENT_EDIT);
        $review = $this->reviews->owned($establishment, $id);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->reviewService->reply($review, $this->currentUser(), (string) $input->body),
                ReviewFlash::REPLIED->text(),
            );
        }

        return $this->redirectToRoute('pro_reviews', ReviewRequestMapper::proReturn($request));
    }

    #[Csrf('pro')]
    #[Route('/pro/reviews/{id}/report', name: 'pro_review_report', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function report(
        Request $request,
        int $id,
        #[MapRequestPayload(validate: false)] ReviewReportInput $input,
    ): RedirectResponse {
        $establishment = $this->permittedEstablishment(Permission::ESTABLISHMENT_EDIT);
        $review = $this->reviews->owned($establishment, $id);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->reviewService->report($review, $this->currentUser(), (string) $input->reason),
                ReviewFlash::REPORTED->text(),
            );
        }

        return $this->redirectToRoute('pro_reviews', ReviewRequestMapper::proReturn($request));
    }
}