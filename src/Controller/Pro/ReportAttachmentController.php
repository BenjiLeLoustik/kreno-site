<?php

namespace App\Controller\Pro;

use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Provider\ProContextProvider;
use App\Company\Security\PermissionResolver;
use App\Report\Provider\EstablishmentReportProvider;
use App\Report\Service\ReportService;
use App\Shared\Helper\DownloadHelper;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class ReportAttachmentController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        ProContextProvider $pro,
        private EstablishmentReportProvider $reports,
        private ReportService $reportService,
    ) {
        parent::__construct($context, $resolver, $pro);
    }

    #[Route(
        '/pro/reports/{id}/attachment',
        name: 'pro_report_attachment',
        methods: ['GET'],
        requirements: ['id' => '\d+'],
    )]
    public function download(int $id): Response
    {
        $establishment = $this->currentEstablishment();
        $report = $this->reports->owned($establishment, $id);
        $this->requirePermission($this->reports->permission($report), $establishment);

        return DownloadHelper::response(
            $this->reportService->attachment($report),
            $this->reportService->attachmentName($report),
            true,
        );
    }
}