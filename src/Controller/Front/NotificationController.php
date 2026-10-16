<?php

namespace App\Controller\Front;

use App\Entity\User\UserNotification;
use App\Repository\User\UserNotificationRepository;
use App\User\Presenter\NotificationPresenter;
use App\User\Service\NotificationService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class NotificationController extends AbstractFrontController
{
    public function __construct(
        private UserNotificationRepository $notifications,
        private NotificationService $notificationService,
        private NotificationPresenter $presenter,
    ) {
    }

    #[Route('/account/notifications', name: 'account_notifications', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->currentUser();

        return $this->render('front/account/notification/index.html.twig', [
            'unread' => $this->notifications->countUnread($user),
            'notifications' => $this->presenter->presentAll($this->notifications->findLatestForUser($user)),
        ]);
    }

    #[Csrf('account')]
    #[Route('/account/notifications/read-all', name: 'account_notifications_read_all', methods: ['POST'])]
    public function readAll(): RedirectResponse
    {
        $count = $this->notificationService->markAllRead($this->currentUser());
        $this->addFlash('success', $count > 0 ? 'Toutes les notifications sont marquées comme lues.' : 'Aucune notification non lue.');

        return $this->redirectToRoute('account_notifications');
    }

    #[Csrf('account')]
    #[Route('/account/notifications/{id}/read', name: 'account_notification_read', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function read(int $id): RedirectResponse
    {
        $user = $this->currentUser();
        $notification = $this->notifications->find($id);

        if (!$notification instanceof UserNotification || $notification->getUser() !== $user) {
            throw $this->createNotFoundException('Notification introuvable.');
        }

        $this->notificationService->markRead($notification);
        $link = $notification->getLink();

        return $link !== null && $link !== '' ? $this->redirect($link) : $this->redirectToRoute('account_notifications');
    }
}