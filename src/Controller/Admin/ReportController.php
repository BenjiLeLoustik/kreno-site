<?php

namespace App\Controller\Admin;

use App\Entity\Report\Report;
use App\Report\Dto\ReportDecisionInput;
use App\Report\Dto\ReportMessageInput;
use App\Report\Enum\ReportDirection;
use App\Report\Helper\ReportStatusHelper;
use App\Report\Presenter\ReportPresenter;
use App\Report\Service\ReportModerationService;
use App\Repository\Report\ReportRepository;
use App\User\Enum\UserRole;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class ReportController extends AbstractAdminController
{
    public const FILTERS = [
        'open' => ['label' => 'Ouverts', 'statuses' => ReportRepository::OPEN_STATUSES],
        'closed' => ['label' => 'Clos', 'statuses' => ReportRepository::CLOSED_STATUSES],
        'all' => ['label' => 'Tous', 'statuses' => [...ReportRepository::OPEN_STATUSES, ...ReportRepository::CLOSED_STATUSES]],
    ];

    public function __construct(
        private ReportRepository $reports,
        private ReportModerationService $moderation,
        private ReportPresenter $presenter,
    ) {
    }

    #[Route('/admin/reports', name: 'admin_reports', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->currentAdmin(UserRole::MODERATOR);

        return $this->page($request, null);
    }

    #[Route('/admin/reports/{id}', name: 'admin_report_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Request $request, int $id): Response
    {
        $this->currentAdmin(UserRole::MODERATOR);

        return $this->page($request, $this->findReport($id));
    }

    #[Csrf('admin_report')]
    #[Route('/admin/reports/{id}/review', name: 'admin_report_review', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function review(int $id): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $this->moderation->review($this->findReport($id), $admin);
        $this->addFlash('success', 'Signalement pris en charge.');

        return $this->redirectToRoute('admin_report_show', ['id' => $id]);
    }

    #[Csrf('admin_report')]
    #[Route('/admin/reports/{id}/notes', name: 'admin_report_note', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function note(int $id, #[MapRequestPayload(validate: false)] ReportMessageInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $report = $this->findReport($id);
        $error = $this->validate($input)->first();

        if ($error !== null) {
            $this->addFlash('error', $error);
        } else {
            $this->moderation->note($report, $admin, (string) $input->body);
            $this->addFlash('success', 'Note interne ajoutée.');
        }

        return $this->redirectToRoute('admin_report_show', ['id' => $id]);
    }

    #[Csrf('admin_report')]
    #[Route('/admin/reports/{id}/decision', name: 'admin_report_decision', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function decision(int $id, #[MapRequestPayload(validate: false)] ReportDecisionInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $report = $this->findReport($id);
        $error = $this->validate($input)->first();

        if ($error !== null) {
            $this->addFlash('error', $error);
        } elseif (!ReportStatusHelper::isOpen($report)) {
            $this->addFlash('error', 'Ce signalement est déjà clos.');
        } else {
            $this->moderation->decide($report, $admin, $input);
            $this->addFlash('success', 'Décision enregistrée. Les deux parties sont prévenues par e-mail.');
        }

        return $this->redirectToRoute('admin_report_show', ['id' => $id]);
    }

    private function page(Request $request, ?Report $selected): Response
    {
        $filter = $request->query->getString('status', 'open');
        $filter = isset(self::FILTERS[$filter]) ? $filter : 'open';
        $direction = ReportDirection::tryFrom($request->query->getString('direction'));
        $items = $this->reports->findForAdmin(self::FILTERS[$filter]['statuses'], $direction);
        $selected ??= $items[0] ?? null;

        return $this->render('admin/report/index.html.twig', [
            'filter' => $filter,
            'direction' => $direction?->value,
            'filters' => array_map(static fn (string $key, array $definition): array => ['key' => $key, 'label' => $definition['label']], array_keys(self::FILTERS), self::FILTERS),
            'directions' => array_map(static fn (ReportDirection $case): array => ['key' => $case->value, 'label' => $case->label()], ReportDirection::cases()),
            'openCount' => $this->reports->countOpen(),
            'rows' => $this->presenter->forAdminList($items),
            'selected' => $selected !== null ? $this->presenter->forAdminDetail($selected) : null,
        ]);
    }

    private function findReport(int $id): Report
    {
        $report = $this->reports->find($id);

        return $report instanceof Report ? $report : throw $this->createNotFoundException('Signalement introuvable.');
    }
}