<?php

namespace App\Controller\Pro;

use App\Booking\Enum\BookingStatus;
use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Booking;
use App\Entity\Report\Report;
use App\Report\Dto\EstablishmentReportInput;
use App\Report\Dto\ReportMessageInput;
use App\Report\Enum\ReportDirection;
use App\Report\Helper\ReportReasonHelper;
use App\Report\Presenter\ReportPresenter;
use App\Report\Service\ReportService;
use App\Repository\BookingRepository;
use App\Repository\Report\ReportRepository;
use App\Website\Provider\WebsiteProvider;
use DateTimeImmutable;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class ReportController extends AbstractProController
{
    public const TABS = [
        'mine' => 'Mes signalements',
        'received' => 'Concernant l’établissement',
    ];

    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        private ReportService $reportService,
        private ReportRepository $reports,
        private BookingRepository $bookings,
        private WebsiteProvider $website,
        private ReportPresenter $presenter,
    ) {
        parent::__construct($context, $resolver);
    }

    #[Route('/pro/reports', name: 'pro_reports', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $member = $this->currentMember();
        $establishment = $this->currentEstablishment();
        $canReport = $this->resolver->memberCan($member, Permission::BOOKINGS_MANAGE, $establishment);
        $canSeeReceived = $this->resolver->memberCan($member, Permission::ESTABLISHMENT_EDIT, $establishment);

        if (!$canReport && !$canSeeReceived) {
            throw $this->createAccessDeniedException('Votre rôle ne permet pas cette action.');
        }

        $tab = $request->query->getString('tab', $canReport ? 'mine' : 'received');
        $tab = ($tab === 'received' && $canSeeReceived) || !$canReport ? 'received' : 'mine';
        $mine = $canReport ? $this->reports->findByEstablishment($establishment, ReportDirection::FROM_ESTABLISHMENT) : [];
        $received = $canSeeReceived ? $this->reports->findByEstablishment($establishment, ReportDirection::FROM_CUSTOMER) : [];
        $rule = $this->website->getRule();

        return $this->render('pro/report/index.html.twig', [
            'context' => $this->proContext(),
            'tab' => $tab,
            'tabs' => array_values(array_filter([
                $canReport ? ['key' => 'mine', 'label' => self::TABS['mine'], 'count' => count($mine)] : null,
                $canSeeReceived ? ['key' => 'received', 'label' => self::TABS['received'], 'count' => count($received)] : null,
            ])),
            'rows' => $tab === 'mine'
                ? array_map(fn (Report $report): array => $this->presenter->forProMine($report), $mine)
                : array_map(fn (Report $report): array => $this->presenter->forProReceived($report), $received),
            'form' => $canReport ? [
                'bookings' => array_map(fn (Booking $booking): array => ['id' => $booking->getId(), 'label' => $this->presenter->proBookingLabel($booking)], $this->bookings->findForEstablishment($establishment, [BookingStatus::CONFIRMED, BookingStatus::HONORED, BookingStatus::NO_SHOW], null, new DateTimeImmutable())),
                'reasons' => ReportReasonHelper::choices(ReportDirection::FROM_ESTABLISHMENT, $request->query->getString('reason') ?: 'no_show'),
                'bookingId' => $request->query->getInt('booking') ?: null,
                'notice' => sprintf('Une absence confirmée compte pour la suspension automatique du client (%d absences en %d jours).', $rule->getToleratedNoShows(), $rule->getNoShowWindowDays()),
            ] : null,
        ]);
    }

    #[Csrf('pro')]
    #[Route('/pro/reports', name: 'pro_report_create', methods: ['POST'])]
    public function create(#[MapRequestPayload(validate: false)] EstablishmentReportInput $input): RedirectResponse
    {
        $establishment = $this->currentEstablishment();
        $this->requirePermission(Permission::BOOKINGS_MANAGE, $establishment);
        $report = null;

        if (!$this->invalid($input)) {
            $this->attempt(function () use ($establishment, $input, &$report): void {
                $report = $this->reportService->createFromEstablishment($establishment, $this->currentUser(), $input);
            }, 'Signalement envoyé. L\'équipe Kréno l\'examine sous 48 h.');
        }

        return $report !== null ? $this->redirectToRoute('pro_report_show', ['id' => $report->getId()]) : $this->redirectToRoute('pro_reports');
    }

    #[Route('/pro/reports/{id}', name: 'pro_report_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $report = $this->findReport($id);
        $received = $report->getDirection() === ReportDirection::FROM_CUSTOMER;

        return $this->render('pro/report/show.html.twig', [
            'context' => $this->proContext(),
            'received' => $received,
            'report' => $received ? $this->presenter->forProReceived($report) : $this->presenter->forProMine($report),
        ]);
    }

    #[Csrf('pro')]
    #[Route('/pro/reports/{id}/messages', name: 'pro_report_message', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function message(int $id, #[MapRequestPayload(validate: false)] ReportMessageInput $input): RedirectResponse
    {
        $report = $this->findReport($id);

        if (!$this->invalid($input)) {
            $report->getDirection() === ReportDirection::FROM_CUSTOMER
                ? $this->attempt(fn () => $this->reportService->replyAsEstablishment($report, $this->currentUser(), (string) $input->body), 'Réponse envoyée à l\'équipe Kréno.')
                : $this->attempt(fn () => $this->reportService->post($report, $this->currentUser(), (string) $input->body), 'Message envoyé.');
        }

        return $this->redirectToRoute('pro_report_show', ['id' => $id]);
    }

    private function findReport(int $id): Report
    {
        $establishment = $this->currentEstablishment();
        $report = $this->reports->findOneForEstablishment($establishment, $id) ?? throw $this->createNotFoundException('Signalement introuvable.');
        $this->requirePermission($report->getDirection() === ReportDirection::FROM_CUSTOMER ? Permission::ESTABLISHMENT_EDIT : Permission::BOOKINGS_MANAGE, $establishment);

        return $report;
    }
}