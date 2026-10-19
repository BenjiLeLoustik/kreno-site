<?php

namespace App\Controller\Front;

use App\Report\Provider\CustomerReportProvider;
use App\Report\Service\ReportService;
use App\Shared\Helper\DownloadHelper;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class ReportAttachmentController extends AbstractFrontController
{
    public function __construct(
        private CustomerReportProvider $reports,
        private ReportService $reportService,
    ) {
    }

    #[Route(
        '/account/reports/{id}/attachment',
        name: 'account_report_attachment',
        methods: ['GET'],
        requirements: ['id' => '\d+'],
    )]
    public function download(int $id): Response
    {
        $report = $this->reports->owned($id, $this->currentUser());

        return DownloadHelper::response(
            $this->reportService->attachment($report),
            $this->reportService->attachmentName($report),
            true,
        );
    }
}