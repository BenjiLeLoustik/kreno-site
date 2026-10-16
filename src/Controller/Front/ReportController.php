<?php

namespace App\Controller\Front;

use App\Entity\Report\Report;
use App\Report\Enum\ReportFlash;
use App\Report\Mapper\ReportRequestMapper;
use App\Report\Provider\CustomerReportProvider;
use App\Report\Service\ReportService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class ReportController extends AbstractFrontController
{
    public function __construct(
        private ReportService $reportService,
        private CustomerReportProvider $reports,
    ) {
    }

    #[Route('/account/reports', name: 'account_reports', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $this->reports->index($this->currentUser(), ReportRequestMapper::selectedBooking($request));

        return $this->render('front/account/report/index.html.twig', $page);
    }

    #[Csrf('account')]
    #[Route('/account/reports', name: 'account_report_create', methods: ['POST'])]
    public function create(Request $request): RedirectResponse
    {
        $input = ReportRequestMapper::customer($request);
        $file = ReportRequestMapper::attachment($request);
        $report = null;

        if (!$this->invalid($input)) {
            $this->attempt(function () use ($input, $file, &$report): void {
                $report = $this->reportService->createFromCustomer($this->currentUser(), $input, $file);
            });
        }

        if (!$report instanceof Report) {
            $this->reports->remember(ReportRequestMapper::old($input));

            return $this->redirectToRoute('account_reports');
        }

        $this->addFlash('success', ReportFlash::SENT->text());

        return $this->redirectToRoute('account_report_show', ['id' => $report->getId()]);
    }

    #[Route('/account/reports/{id}', name: 'account_report_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        return $this->render('front/account/report/show.html.twig', $this->reports->show($id, $this->currentUser()));
    }

    #[Csrf('account')]
    #[Route(
        '/account/reports/{id}/messages',
        name: 'account_report_message',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function message(Request $request, int $id): RedirectResponse
    {
        $user = $this->currentUser();
        $report = $this->reports->owned($id, $user);
        $input = ReportRequestMapper::message($request);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->reportService->replyAsReporter($report, $user, (string) $input->body),
                ReportFlash::MESSAGE_SENT->text(),
            );
        }

        return $this->redirectToRoute('account_report_show', ['id' => $id]);
    }
}