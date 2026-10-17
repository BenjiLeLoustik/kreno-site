<?php

namespace App\Controller\Admin;

use App\Report\Provider\AdminReportPageProvider;
use App\Report\Service\ReportService;
use App\Shared\Helper\DownloadHelper;
use App\User\Enum\UserRole;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class ReportAttachmentController extends AbstractAdminController
{
    public function __construct(
        private AdminReportPageProvider $reports,
        private ReportService $reportService,
    ) {
    }

    #[Route(
        '/admin/reports/{id}/attachment',
        name: 'admin_report_attachment',
        methods: ['GET'],
        requirements: ['id' => '\d+'],
    )]
    public function download(int $id): Response
    {
        $this->currentAdmin(UserRole::MODERATOR);
        $report = $this->reports->report($id);

        return DownloadHelper::response(
            $this->reportService->attachment($report),
            $this->reportService->attachmentName($report),
            true,
        );
    }
}