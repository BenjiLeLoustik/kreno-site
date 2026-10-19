<?php

namespace App\Mail\Listener;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentSlot;
use App\Entity\User\User;
use App\Entity\User\UserAlert;
use App\Establishment\Event\SlotPublishedEvent;
use App\Establishment\Service\AlertMatchService;
use App\Mail\Enum\MailSubject;
use App\Mail\Enum\MailText;
use App\Mail\Presenter\MailContextPresenter;
use App\Mail\Service\MailService;
use App\Repository\Establishment\EstablishmentFollowerRepository;
use App\Repository\User\UserAlertRepository;
use App\User\Enum\NotificationType;
use App\User\Enum\UserStatus;
use App\User\Service\NotificationService;
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

        $recipients = $this->addAlertMatches($this->followerRecipients($establishment), $establishment, $slot);
        $publisher = $slot->getPublishedBy();
        $context = $this->context->slot($slot);
        $sent = 0;

        foreach ($recipients as $recipient) {
            $user = $recipient['user'];

            if ($user === $publisher || $user->getStatus() !== UserStatus::ACTIVE) {
                continue;
            }

            $this->notify($user, $establishment, $context);
            $this->notifyByEmail($user, $recipient['alert'], $recipient['follower'], $context);
            ++$sent;
        }

        if ($sent > 0) {
            $this->orm->flush();
        }
    }

    private function followerRecipients(CompanyEstablishment $establishment): array
    {
        $recipients = [];

        foreach ($this->followers->findByEstablishment($establishment) as $follower) {
            $user = $follower->getUser();

            if ($user !== null) {
                $recipients[$user->getId()] = ['user' => $user, 'follower' => true, 'alert' => null];
            }
        }

        return $recipients;
    }

    private function addAlertMatches(
        array $recipients,
        CompanyEstablishment $establishment,
        EstablishmentSlot $slot,
    ): array {
        if ($establishment->getCity() === null) {
            return $recipients;
        }

        foreach ($this->alerts->findEnabledInCity($establishment->getCity()) as $alert) {
            $user = $alert->getUser();

            if ($user === null || isset($recipients[$user->getId()]['alert'])) {
                continue;
            }

            if (!$this->matcher->matches($alert, $slot)) {
                continue;
            }

            $recipients[$user->getId()] = [
                'user' => $user,
                'follower' => $recipients[$user->getId()]['follower'] ?? false,
                'alert' => $alert,
            ];
        }

        return $recipients;
    }

    private function notify(User $user, CompanyEstablishment $establishment, array $context): void
    {
        $title = MailText::SLOT_ALERT_TITLE->text(
            $establishment->getName(),
            $context['day'],
            $context['time'],
            $context['price'],
        );
        $body = MailText::SLOT_ALERT_BODY->text($context['service'], $context['price'], $context['originalPrice']);

        $this->notifications->notify($user, NotificationType::SLOT_ALERT, $title, $body, $context['url'], false);
    }

    private function notifyByEmail(User $user, ?UserAlert $alert, bool $follower, array $context): void
    {
        $subject = MailSubject::SLOT_ALERT->text(
            $context['establishment']['name'] ?? '',
            $context['day'],
            $context['time'],
            $context['discount'],
        );

        $this->mail->toUser(MailService::CLIENT, $user, $subject, 'slot-alert', [
            'slot' => $context,
            'follower' => $follower,
            'alert' => $this->alertContext($alert),
            'alertsUrl' => $this->mail->url('account_alerts'),
            'settingsUrl' => $this->mail->url('account_settings'),
        ], true);
    }

    private function alertContext(?UserAlert $alert): ?array
    {
        if ($alert === null) {
            return null;
        }

        return ['label' => $alert->getLabel(), 'description' => $this->matcher->describe($alert)];
    }
}