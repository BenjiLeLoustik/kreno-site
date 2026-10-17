<?php

namespace App\Controller\Admin;

use App\Log\Enum\AppLogFlash;
use App\Log\Mapper\AppLogRequestMapper;
use App\Log\Provider\AppLogPageProvider;
use App\Log\Service\AppLogService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class AppLogController extends AbstractAdminController
{
    public function __construct(
        private AppLogService $service,
        private AppLogPageProvider $pages,
    ) {
    }

    #[Route('/admin/errors', name: 'admin_app_logs', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->currentAdmin();
        $page = $this->pages->index(
            AppLogRequestMapper::page($request),
            AppLogRequestMapper::filterStatus($request),
            AppLogRequestMapper::search($request),
        );

        return $this->render('admin/app_log/index.html.twig', $page);
    }

    #[Route('/admin/errors/{id}', name: 'admin_app_log_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Request $request, int $id): Response
    {
        $this->currentAdmin();
        $page = $this->pages->index(
            AppLogRequestMapper::page($request),
            AppLogRequestMapper::filterStatus($request),
            AppLogRequestMapper::search($request),
            $this->pages->log($id),
        );

        return $this->render('admin/app_log/index.html.twig', $page);
    }

    #[Csrf('admin_app_log')]
    #[Route(
        '/admin/errors/{id}/status',
        name: 'admin_app_log_status',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function status(Request $request, int $id): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $log = $this->pages->log($id);
        $status = AppLogRequestMapper::status($request);
        $back = ['id' => $id] + AppLogRequestMapper::returnQuery($request);

        if ($status === null) {
            $this->addFlash('error', AppLogFlash::UNKNOWN_STATUS->text());

            return $this->redirectToRoute('admin_app_log_show', $back);
        }

        $this->attempt(
            fn () => $this->service->changeStatus($log, $status, $admin, AppLogRequestMapper::note($request)),
            AppLogFlash::statusChanged($status),
        );

        return $this->redirectToRoute('admin_app_log_show', $back);
    }

    #[Csrf('admin_app_log')]
    #[Route(
        '/admin/errors/{id}/delete',
        name: 'admin_app_log_delete',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function delete(Request $request, int $id): RedirectResponse
    {
        $this->currentAdmin();
        $log = $this->pages->log($id);
        $this->attempt(fn () => $this->service->delete($log), AppLogFlash::DELETED->text());

        return $this->redirectToRoute('admin_app_logs', AppLogRequestMapper::returnQuery($request));
    }

    #[Csrf('admin_app_log')]
    #[Route('/admin/errors/purge', name: 'admin_app_log_purge', methods: ['POST'])]
    public function purge(): RedirectResponse
    {
        $this->currentAdmin();
        $count = 0;
        $this->attempt(function () use (&$count): void {
            $count = $this->service->purge();
        }, AppLogFlash::PURGED->text());

        if ($count > 0) {
            $this->addFlash('info', AppLogFlash::purgedCount($count));
        }

        return $this->redirectToRoute('admin_app_logs');
    }
}