<?php

namespace App\Controller\Pro;

use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Report\Enum\ReportDirection;
use App\Report\Service\ReportService;
use App\Repository\Report\ReportRepository;
use App\Shared\Helper\DownloadHelper;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class ReportAttachmentController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        private ReportRepository $reports,
        private ReportService $reportService,
    ) {
        parent::__construct($context, $resolver);
    }

    #[Route('/pro/reports/{id}/attachment', name: 'pro_report_attachment', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function download(int $id): Response
    {
        $establishment = $this->currentEstablishment();
        $report = $this->reports->findOneForEstablishment($establishment, $id) ?? throw $this->createNotFoundException('Signalement introuvable.');
        $this->requirePermission($report->getDirection() === ReportDirection::FROM_CUSTOMER ? Permission::ESTABLISHMENT_EDIT : Permission::BOOKINGS_MANAGE, $establishment);

        return DownloadHelper::response($this->reportService->attachment($report), $this->reportService->attachmentName($report), true);
    }
}