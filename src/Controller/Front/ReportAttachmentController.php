<?php

namespace App\Controller\Front;

use App\Report\Service\ReportService;
use App\Repository\Report\ReportRepository;
use App\Shared\Helper\DownloadHelper;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class ReportAttachmentController extends AbstractFrontController
{
    public function __construct(
        private ReportRepository $reports,
        private ReportService $reportService,
    ) {
    }

    #[Route('/account/reports/{id}/attachment', name: 'account_report_attachment', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function download(int $id): Response
    {
        $report = $this->reports->findOneForReporter($this->currentUser(), $id) ?? throw $this->createNotFoundException('Signalement introuvable.');

        return DownloadHelper::response($this->reportService->attachment($report), $this->reportService->attachmentName($report), true);
    }
}