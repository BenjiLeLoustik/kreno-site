<?php

namespace App\Controller\Front;

use App\Booking\Enum\BookingStatus;
use App\Entity\Booking;
use App\Entity\Report\Report;
use App\Entity\User\User;
use App\Report\Dto\CustomerReportInput;
use App\Report\Dto\ReportMessageInput;
use App\Report\Enum\ReportDirection;
use App\Report\Helper\ReportReasonHelper;
use App\Report\Presenter\ReportPresenter;
use App\Report\Service\ReportService;
use App\Repository\BookingRepository;
use App\Repository\Report\ReportRepository;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Request\UploadedFile;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Session\SessionManagerInterface;

class ReportController extends AbstractFrontController
{
    public const OLD_REPORT = 'account.report.old';

    public function __construct(
        private ReportService $reportService,
        private ReportRepository $reports,
        private BookingRepository $bookings,
        private ReportPresenter $presenter,
        private SessionManagerInterface $session,
    ) {
    }

    #[Route('/account/reports', name: 'account_reports', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->currentUser();
        $old = $this->session->get(self::OLD_REPORT);
        $this->session->remove(self::OLD_REPORT);
        $old = is_array($old) ? $old : [];
        $selected = $old['booking'] ?? ($request->query->getString('booking') ?: null);

        return $this->render('front/account/report/index.html.twig', [
            'form' => [
                'bookings' => $this->reportableBookings($user),
                'selected' => $selected,
                'reason' => $old['reason'] ?? null,
                'description' => $old['description'] ?? null,
                'reasons' => ReportReasonHelper::choices(ReportDirection::FROM_CUSTOMER, $old['reason'] ?? null),
                'windowHours' => ReportService::CUSTOMER_WINDOW_HOURS,
            ],
            'reports' => $this->presenter->forCustomerList($this->reports->findForReporter($user)),
        ]);
    }

    #[Csrf('account')]
    #[Route('/account/reports', name: 'account_report_create', methods: ['POST'])]
    public function create(Request $request): RedirectResponse
    {
        $input = new CustomerReportInput();
        $input->booking = trim($request->request->getString('booking')) ?: null;
        $input->reason = $request->request->getString('reason') ?: null;
        $input->description = trim($request->request->getString('description')) ?: null;
        $file = $request->files->get('attachment');
        $report = null;

        if (!$this->invalid($input)) {
            $this->attempt(function () use ($input, $file, &$report): void {
                $report = $this->reportService->createFromCustomer($this->currentUser(), $input, $file instanceof UploadedFile && $file->isValid() ? $file : null);
            });
        }

        if (!$report instanceof Report) {
            $this->session->set(self::OLD_REPORT, ['booking' => $input->booking, 'reason' => $input->reason, 'description' => $input->description]);

            return $this->redirectToRoute('account_reports');
        }

        $this->addFlash('success', 'Signalement envoyé. Nous revenons vers vous par e-mail sous 48 h.');

        return $this->redirectToRoute('account_report_show', ['id' => $report->getId()]);
    }

    #[Route('/account/reports/{id}', name: 'account_report_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        return $this->render('front/account/report/show.html.twig', [
            'report' => $this->presenter->forCustomer($this->findReport($id)),
        ]);
    }

    #[Csrf('account')]
    #[Route('/account/reports/{id}/messages', name: 'account_report_message', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function message(Request $request, int $id): RedirectResponse
    {
        $report = $this->findReport($id);
        $input = new ReportMessageInput();
        $input->body = trim($request->request->getString('body')) ?: null;

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->reportService->replyAsReporter($report, $this->currentUser(), (string) $input->body), 'Message envoyé.');
        }

        return $this->redirectToRoute('account_report_show', ['id' => $id]);
    }

    private function reportableBookings(User $user): array
    {
        $items = [];
        $statuses = [BookingStatus::CONFIRMED, BookingStatus::HONORED, BookingStatus::NO_SHOW, BookingStatus::CANCELLED_BY_ESTABLISHMENT];

        foreach ($this->bookings->findForCustomer($user, $statuses, null, null, 30) as $booking) {
            if ($booking instanceof Booking && $this->reportService->canCustomerReport($booking)) {
                $establishment = $booking->getSlot()?->getEstablishment();
                $items[] = [
                    'reference' => $booking->getReference(),
                    'label' => $this->presenter->bookingLabel($booking),
                    'establishment' => $establishment !== null ? trim(sprintf('%s · %s', $establishment->getName(), $establishment->getLocality()), ' ·') : null,
                ];
            }
        }

        return $items;
    }

    private function findReport(int $id): Report
    {
        return $this->reports->findOneForReporter($this->currentUser(), $id) ?? throw $this->createNotFoundException('Signalement introuvable.');
    }
}