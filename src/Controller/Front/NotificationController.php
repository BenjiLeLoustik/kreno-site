<?php

namespace App\Controller\Front;

use App\User\Enum\NotificationFlash;
use App\User\Provider\NotificationPageProvider;
use App\User\Service\NotificationService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class NotificationController extends AbstractFrontController
{
    public function __construct(
        private NotificationService $notificationService,
        private NotificationPageProvider $pages,
    ) {
    }

    #[Route('/account/notifications', name: 'account_notifications', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('front/account/notification/index.html.twig', $this->pages->index($this->currentUser()));
    }

    #[Csrf('account')]
    #[Route('/account/notifications/read-all', name: 'account_notifications_read_all', methods: ['POST'])]
    public function readAll(): RedirectResponse
    {
        $count = $this->notificationService->markAllRead($this->currentUser());
        $this->addFlash('success', NotificationFlash::afterReadAll($count)->text());

        return $this->redirectToRoute('account_notifications');
    }

    #[Csrf('account')]
    #[Route(
        '/account/notifications/{id}/read',
        name: 'account_notification_read',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function read(int $id): RedirectResponse
    {
        $notification = $this->pages->owned($id, $this->currentUser());
        $this->notificationService->markRead($notification);
        $link = $this->pages->link($notification);

        if ($link === null) {
            return $this->redirectToRoute('account_notifications');
        }

        return $this->redirect($link);
    }
}