<?php

namespace App\Controller\Admin;

use App\Entity\Report\Report;
use App\Report\Service\ReportService;
use App\Repository\Report\ReportRepository;
use App\Shared\Helper\DownloadHelper;
use App\User\Enum\UserRole;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class ReportAttachmentController extends AbstractAdminController
{
    public function __construct(
        private ReportRepository $reports,
        private ReportService $reportService,
    ) {
    }

    #[Route('/admin/reports/{id}/attachment', name: 'admin_report_attachment', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function download(int $id): Response
    {
        $this->currentAdmin(UserRole::MODERATOR);
        $report = $this->reports->find($id);

        if (!$report instanceof Report) {
            throw $this->createNotFoundException('Signalement introuvable.');
        }

        return DownloadHelper::response($this->reportService->attachment($report), $this->reportService->attachmentName($report), true);
    }
}