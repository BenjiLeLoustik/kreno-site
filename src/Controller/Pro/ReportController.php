<?php

namespace App\Controller\Pro;

use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Provider\ProContextProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Report\Report;
use App\Report\Dto\EstablishmentReportInput;
use App\Report\Dto\ReportMessageInput;
use App\Report\Enum\ReportFlash;
use App\Report\Mapper\ProReportRequestMapper;
use App\Report\Provider\EstablishmentReportProvider;
use App\Report\Service\EstablishmentReportMessageService;
use App\Report\Service\ReportService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class ReportController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        ProContextProvider $pro,
        private ReportService $reportService,
        private EstablishmentReportMessageService $messages,
        private EstablishmentReportProvider $reports,
    ) {
        parent::__construct($context, $resolver, $pro);
    }

    #[Route('/pro/reports', name: 'pro_reports', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $this->reports->index(
            $this->currentMember(),
            $this->currentEstablishment(),
            ProReportRequestMapper::tab($request),
            ProReportRequestMapper::reason($request),
            ProReportRequestMapper::bookingId($request),
        );

        return $this->renderPro('pro/report/index.html.twig', $page);
    }

    #[Csrf('pro')]
    #[Route('/pro/reports', name: 'pro_report_create', methods: ['POST'])]
    public function create(#[MapRequestPayload(validate: false)] EstablishmentReportInput $input): RedirectResponse
    {
        $establishment = $this->permittedEstablishment(Permission::BOOKINGS_MANAGE);
        $report = null;

        if (!$this->invalid($input)) {
            $this->attempt(function () use ($establishment, $input, &$report): void {
                $report = $this->reportService->createFromEstablishment($establishment, $this->currentUser(), $input);
            }, ReportFlash::ESTABLISHMENT_SENT->text());
        }

        if (!$report instanceof Report) {
            return $this->redirectToRoute('pro_reports');
        }

        return $this->redirectToRoute('pro_report_show', ['id' => $report->getId()]);
    }

    #[Route('/pro/reports/{id}', name: 'pro_report_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $establishment = $this->currentEstablishment();
        $report = $this->reports->owned($establishment, $id);
        $this->requirePermission($this->reports->permission($report), $establishment);

        return $this->renderPro('pro/report/show.html.twig', $this->reports->show($report));
    }

    #[Csrf('pro')]
    #[Route(
        '/pro/reports/{id}/messages',
        name: 'pro_report_message',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function message(int $id, #[MapRequestPayload(validate: false)] ReportMessageInput $input): RedirectResponse
    {
        $establishment = $this->currentEstablishment();
        $report = $this->reports->owned($establishment, $id);
        $this->requirePermission($this->reports->permission($report), $establishment);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->messages->send($report, $this->currentUser(), (string) $input->body),
                ReportFlash::establishmentMessage($report->getDirection())->text(),
            );
        }

        return $this->redirectToRoute('pro_report_show', ['id' => $id]);
    }
}