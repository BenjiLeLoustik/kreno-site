<?php

namespace App\Controller\Admin;

use App\Report\Dto\ReportDecisionInput;
use App\Report\Dto\ReportMessageInput;
use App\Report\Enum\AdminReportFlash;
use App\Report\Mapper\AdminReportRequestMapper;
use App\Report\Provider\AdminReportPageProvider;
use App\Report\Service\AdminReportDecisionService;
use App\Report\Service\ReportModerationService;
use App\User\Enum\UserRole;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class ReportController extends AbstractAdminController
{
    public function __construct(
        private ReportModerationService $moderation,
        private AdminReportDecisionService $decisions,
        private AdminReportPageProvider $pages,
    ) {
    }

    #[Route('/admin/reports', name: 'admin_reports', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->currentAdmin(UserRole::MODERATOR);
        $page = $this->pages->index(
            AdminReportRequestMapper::filter($request),
            AdminReportRequestMapper::direction($request),
        );

        return $this->render('admin/report/index.html.twig', $page);
    }

    #[Route('/admin/reports/{id}', name: 'admin_report_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Request $request, int $id): Response
    {
        $this->currentAdmin(UserRole::MODERATOR);
        $page = $this->pages->index(
            AdminReportRequestMapper::filter($request),
            AdminReportRequestMapper::direction($request),
            $this->pages->report($id),
        );

        return $this->render('admin/report/index.html.twig', $page);
    }

    #[Csrf('admin_report')]
    #[Route(
        '/admin/reports/{id}/review',
        name: 'admin_report_review',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function review(int $id): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $report = $this->pages->report($id);
        $this->attempt(fn () => $this->moderation->review($report, $admin), AdminReportFlash::REVIEWED->text());

        return $this->redirectToRoute('admin_report_show', ['id' => $id]);
    }

    #[Csrf('admin_report')]
    #[Route(
        '/admin/reports/{id}/notes',
        name: 'admin_report_note',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function note(int $id, #[MapRequestPayload(validate: false)] ReportMessageInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $report = $this->pages->report($id);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->moderation->note($report, $admin, (string) $input->body),
                AdminReportFlash::NOTE_ADDED->text(),
            );
        }

        return $this->redirectToRoute('admin_report_show', ['id' => $id]);
    }

    #[Csrf('admin_report')]
    #[Route(
        '/admin/reports/{id}/decision',
        name: 'admin_report_decision',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function decision(
        int $id,
        #[MapRequestPayload(validate: false)] ReportDecisionInput $input,
    ): RedirectResponse {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $report = $this->pages->report($id);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->decisions->decide($report, $admin, $input),
                AdminReportFlash::DECIDED->text(),
            );
        }

        return $this->redirectToRoute('admin_report_show', ['id' => $id]);
    }
}