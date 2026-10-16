<?php

namespace App\Mail\Listener;

use App\Entity\User\User;
use App\Entity\User\UserAlert;
use App\Establishment\Event\SlotPublishedEvent;
use App\Establishment\Service\AlertMatchService;
use App\Mail\Presenter\MailContextPresenter;
use App\Mail\Service\MailService;
use App\Repository\Establishment\EstablishmentFollowerRepository;
use App\Repository\User\UserAlertRepository;
use App\User\Service\NotificationService;
use App\User\Enum\NotificationType;
use App\User\Enum\UserStatus;
use NeoPHP\Component\Event\Attribute\AsListener;
use NeoPHP\Package\Orm\OrmManagerInterface;

class SlotAlertListener
{
    public function __construct(
        private MailService $mail,
        private MailContextPresenter $context,
        private NotificationService $notifications,
        private AlertMatchService $matcher,
        private EstablishmentFollowerRepository $followers,
        private UserAlertRepository $alerts,
        private OrmManagerInterface $orm,
    ) {
    }

    #[AsListener(SlotPublishedEvent::class)]
    public function onSlotPublished(SlotPublishedEvent $event): void
    {
        $slot = $event->getSlot();
        $establishment = $slot->getEstablishment();

        if ($establishment === null) {
            return;
        }

        $recipients = [];

        foreach ($this->followers->findByEstablishment($establishment) as $follower) {
            $user = $follower->getUser();

            if ($user !== null) {
                $recipients[$user->getId()] = ['user' => $user, 'follower' => true, 'alert' => null];
            }
        }

        if ($establishment->getCity() !== null) {
            foreach ($this->alerts->findEnabledInCity($establishment->getCity()) as $alert) {
                $user = $alert->getUser();

                if ($user === null || isset($recipients[$user->getId()]['alert']) || !$this->matcher->matches($alert, $slot)) {
                    continue;
                }

                $recipients[$user->getId()] = ['user' => $user, 'follower' => $recipients[$user->getId()]['follower'] ?? false, 'alert' => $alert];
            }
        }

        $publisher = $slot->getPublishedBy();
        $context = $this->context->slot($slot);
        $title = sprintf('%s · %s à %s, %s', $establishment->getName(), $context['day'], $context['time'], $context['price']);
        $sent = 0;

        foreach ($recipients as $recipient) {
            $user = $recipient['user'];

            if ($user === $publisher || $user->getStatus() !== UserStatus::ACTIVE) {
                continue;
            }

            $this->notifications->notify($user, NotificationType::SLOT_ALERT, $title, sprintf('%s · %s au lieu de %s', $context['service'], $context['price'], $context['originalPrice']), $context['url'], false);
            $this->notifyByEmail($user, $recipient['alert'], $recipient['follower'], $context);
            ++$sent;
        }

        if ($sent > 0) {
            $this->orm->flush();
        }
    }

    private function notifyByEmail(User $user, ?UserAlert $alert, bool $follower, array $context): void
    {
        $this->mail->toUser(MailService::CLIENT, $user, sprintf('%s : créneau %s à %s (%s)', $context['establishment']['name'] ?? '', $context['day'], $context['time'], $context['discount']), 'slot-alert', [
            'slot' => $context,
            'follower' => $follower,
            'alert' => $alert !== null ? ['label' => $alert->getLabel(), 'description' => $this->matcher->describe($alert)] : null,
            'alertsUrl' => $this->mail->url('account_alerts'),
            'settingsUrl' => $this->mail->url('account_settings'),
        ], true);
    }
}