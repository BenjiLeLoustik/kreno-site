<?php

namespace App\Controller\Admin;

use App\Entity\AppLog;
use App\Log\Enum\AppLogStatus;
use App\Log\Presenter\AppLogPresenter;
use App\Log\Service\AppLogService;
use App\Repository\AppLogRepository;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class AppLogController extends AbstractAdminController
{
    public const PER_PAGE = 50;

    public function __construct(
        private AppLogRepository $logs,
        private AppLogService $service,
        private AppLogPresenter $presenter,
    ) {
    }

    #[Route('/admin/errors', name: 'admin_app_logs', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->currentAdmin();

        return $this->page($request, null);
    }

    #[Route('/admin/errors/{id}', name: 'admin_app_log_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Request $request, int $id): Response
    {
        $this->currentAdmin();

        return $this->page($request, $this->findLog($id));
    }

    #[Csrf('admin_app_log')]
    #[Route('/admin/errors/{id}/status', name: 'admin_app_log_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function status(Request $request, int $id): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $log = $this->findLog($id);
        $status = AppLogStatus::tryFrom($request->request->getString('status'));

        if ($status === null) {
            $this->addFlash('error', 'Statut inconnu.');
        } else {
            $this->attempt(fn () => $this->service->changeStatus($log, $status, $admin, $request->request->getString('note')), sprintf('Erreur marquée « %s ».', mb_strtolower($status->label())));
        }

        return $this->redirectToRoute('admin_app_log_show', ['id' => $id] + $this->query($request));
    }

    #[Csrf('admin_app_log')]
    #[Route('/admin/errors/{id}/delete', name: 'admin_app_log_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id): RedirectResponse
    {
        $this->currentAdmin();
        $log = $this->findLog($id);
        $this->attempt(fn () => $this->service->delete($log), 'Erreur supprimée.');

        return $this->redirectToRoute('admin_app_logs', $this->query($request));
    }

    #[Csrf('admin_app_log')]
    #[Route('/admin/errors/purge', name: 'admin_app_log_purge', methods: ['POST'])]
    public function purge(): RedirectResponse
    {
        $this->currentAdmin();
        $count = 0;
        $this->attempt(function () use (&$count): void {
            $count = $this->service->purge();
        }, 'Purge terminée.');

        if ($count > 0) {
            $this->addFlash('info', sprintf('%d erreur%s supprimée%s.', $count, $count > 1 ? 's' : '', $count > 1 ? 's' : ''));
        }

        return $this->redirectToRoute('admin_app_logs');
    }

    private function page(Request $request, ?AppLog $selected): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $status = $request->query->has('status') ? AppLogStatus::tryFrom($request->query->getString('status')) : AppLogStatus::NEW;
        $search = trim($request->query->getString('q'));
        $logs = $this->logs->findForAdmin($status, $search !== '' ? $search : null, self::PER_PAGE + 1, ($page - 1) * self::PER_PAGE);
        $hasNext = count($logs) > self::PER_PAGE;
        $logs = array_slice($logs, 0, self::PER_PAGE);
        $selected ??= $logs[0] ?? null;

        return $this->render('admin/app_log/index.html.twig', [
            'filters' => ['q' => $search, 'status' => $status?->value ?? 'all', 'page' => $page],
            'hasNext' => $hasNext,
            'statuses' => array_map(fn (AppLogStatus $case): array => ['value' => $case->value, 'label' => $case->label(), 'count' => $this->logs->countByStatus($case)], AppLogStatus::cases()),
            'logs' => array_map(fn (AppLog $log): array => $this->presenter->row($log), $logs),
            'selected' => $selected !== null ? $this->presenter->detail($selected) : null,
            'retentionDays' => AppLogService::RETENTION_DAYS,
        ]);
    }

    private function query(Request $request): array
    {
        return array_filter(['status' => $request->request->getString('filterStatus', 'new'), 'q' => trim($request->request->getString('filterQ'))], static fn (string $value): bool => $value !== '');
    }

    private function findLog(int $id): AppLog
    {
        $log = $this->logs->find($id);

        return $log instanceof AppLog ? $log : throw $this->createNotFoundException('Erreur introuvable.');
    }
}