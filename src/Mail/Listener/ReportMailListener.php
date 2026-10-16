<?php

namespace App\Mail\Listener;

use App\Entity\Report\Report;
use App\Entity\User\User;
use App\Establishment\Helper\CalendarHelper;
use App\Mail\Presenter\MailContextPresenter;
use App\Mail\Provider\RecipientProvider;
use App\Mail\Service\MailService;
use App\Report\Enum\ReportDirection;
use App\Report\Event\ReportCreatedEvent;
use App\Report\Event\ReportDecidedEvent;
use App\Report\Event\ReportMessagePostedEvent;
use App\Report\Helper\ReportStatusHelper;
use App\Report\Service\ReportService;
use App\Shared\Helper\DateHelper;
use NeoPHP\Component\Event\Attribute\AsListener;

class ReportMailListener
{
    public function __construct(
        private MailService $mail,
        private RecipientProvider $recipients,
        private MailContextPresenter $context,
        private ReportService $reports,
    ) {
    }

    #[AsListener(ReportCreatedEvent::class)]
    public function onCreated(ReportCreatedEvent $event): void
    {
        $report = $event->getReport();
        $reporter = $report->getReporter();
        $context = $this->context->report($report);

        if ($report->getDirection() === ReportDirection::FROM_ESTABLISHMENT) {
            if ($reporter !== null) {
                $this->mail->toUser(MailService::PRO, $reporter, sprintf('Signalement %s transmis à Kréno', $context['reference']), 'pro-report-sent', [
                    'report' => $context,
                    'reportsUrl' => $this->mail->url('pro_reports'),
                ]);
            }

            return;
        }

        if ($reporter !== null) {
            $this->mail->toUser(MailService::SUPPORT, $reporter, 'Nous avons bien reçu ton signalement', 'report-received', [
                'report' => $context,
            ]);
        }

        $deadline = $this->reports->replyDeadline($report);

        $this->mail->send(MailService::PRO, $this->recipients->establishment($report->getEstablishment()), sprintf('Un client a signalé un problème : %d h pour répondre', ReportService::REPLY_WINDOW_HOURS), 'pro-report-received', [
            'report' => $context,
            'replyWithin' => sprintf('%d h', ReportService::REPLY_WINDOW_HOURS),
            'deadline' => $deadline !== null ? sprintf('%s à %s', CalendarHelper::longDate($deadline), DateHelper::time($deadline)) : null,
        ]);
    }

    #[AsListener(ReportMessagePostedEvent::class)]
    public function onMessagePosted(ReportMessagePostedEvent $event): void
    {
        $message = $event->getMessage();
        $report = $message->getReport();
        $author = $message->getAuthor();

        if ($report === null || $author === null || $message->isInternal() || !ReportStatusHelper::isOpen($report)) {
            return;
        }

        $context = [
            'report' => $this->context->report($report),
            'message' => $message->getBody(),
        ];

        if ($author === $report->getReporter()) {
            if ($report->getDirection() === ReportDirection::FROM_CUSTOMER) {
                $this->mail->send(MailService::PRO, $this->recipients->establishment($report->getEstablishment()), sprintf('Nouveau message sur le signalement %s', $context['report']['reference']), 'pro-report-message', $context);
            }

            return;
        }

        if ($this->isEstablishmentSide($report, $author) && $report->getReporter() !== null) {
            $this->mail->toUser(MailService::SUPPORT, $report->getReporter(), sprintf('%s a répondu à ton signalement', $report->getEstablishment()?->getName()), 'report-message', $context);
        }
    }

    #[AsListener(ReportDecidedEvent::class)]
    public function onDecided(ReportDecidedEvent $event): void
    {
        $report = $event->getReport();
        $reporter = $report->getReporter();
        $context = [
            'report' => $this->context->report($report),
            'decision' => $event->getDecision(),
            'message' => $this->lastMessage($report),
        ];

        if ($report->getDirection() === ReportDirection::FROM_ESTABLISHMENT) {
            if ($reporter !== null) {
                $this->mail->toUser(MailService::PRO, $reporter, sprintf('Signalement %s : décision de Kréno', $context['report']['reference']), 'pro-report-decided', $context + [
                        'audience' => 'reporter',
                    ]);
            }

            return;
        }

        if ($reporter !== null) {
            $this->mail->toUser(MailService::SUPPORT, $reporter, sprintf('Ton signalement %s est traité', $context['report']['reference']), 'report-decided', $context);
        }

        if (in_array($event->getDecision(), ['warn', 'suspend', 'resolve'], true)) {
            $this->mail->send(MailService::PRO, $this->recipients->establishment($report->getEstablishment()), sprintf('Signalement %s : décision de Kréno', $context['report']['reference']), 'pro-report-decided', $context + [
                    'audience' => 'establishment',
                ]);
        }
    }

    private function isEstablishmentSide(Report $report, User $author): bool
    {
        foreach ($report->getEstablishment()?->getCompany()?->getMembers() ?? [] as $member) {
            if ($member->getUser() === $author) {
                return true;
            }
        }

        return false;
    }

    private function lastMessage(Report $report): ?string
    {
        $last = null;

        foreach ($report->getMessages() as $message) {
            if (!$message->isInternal()) {
                $last = $message->getBody();
            }
        }

        return $last;
    }
}