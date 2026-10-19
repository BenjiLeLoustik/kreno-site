<?php

namespace App\Mail\Listener;

use App\Entity\Report\Report;
use App\Entity\Report\ReportMessage;
use App\Entity\User\User;
use App\Establishment\Helper\CalendarHelper;
use App\Mail\Enum\MailSubject;
use App\Mail\Enum\MailText;
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
use DateTimeImmutable;
use NeoPHP\Component\Event\Attribute\AsListener;

class ReportMailListener
{
    public const ESTABLISHMENT_DECISIONS = ['warn', 'suspend', 'resolve'];

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
            $this->confirmToEstablishment($reporter, $context);

            return;
        }

        if ($reporter !== null) {
            $subject = MailSubject::REPORT_RECEIVED->text();

            $this->mail->toUser(MailService::SUPPORT, $reporter, $subject, 'report-received', [
                'report' => $context,
            ]);
        }

        $subject = MailSubject::PRO_REPORT_RECEIVED->text(ReportService::REPLY_WINDOW_HOURS);

        $this->sendToEstablishment($report, $subject, 'pro-report-received', [
            'report' => $context,
            'replyWithin' => MailText::HOURS->text(ReportService::REPLY_WINDOW_HOURS),
            'deadline' => $this->dateTime($this->reports->replyDeadline($report)),
        ]);
    }

    #[AsListener(ReportMessagePostedEvent::class)]
    public function onMessagePosted(ReportMessagePostedEvent $event): void
    {
        $message = $event->getMessage();
        $report = $message->getReport();
        $author = $message->getAuthor();

        if ($report === null || $author === null || !$this->isPublic($message, $report)) {
            return;
        }

        $context = [
            'report' => $this->context->report($report),
            'message' => $message->getBody(),
        ];

        if ($author === $report->getReporter()) {
            $this->forwardToEstablishment($report, $context);

            return;
        }

        if ($this->isEstablishmentSide($report, $author) && $report->getReporter() !== null) {
            $subject = MailSubject::REPORT_MESSAGE->text($report->getEstablishment()?->getName());

            $this->mail->toUser(MailService::SUPPORT, $report->getReporter(), $subject, 'report-message', $context);
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
        $reference = $context['report']['reference'];

        if ($report->getDirection() === ReportDirection::FROM_ESTABLISHMENT) {
            $this->sendDecisionToReporter($reporter, $reference, $context);

            return;
        }

        if ($reporter !== null) {
            $subject = MailSubject::REPORT_DECIDED->text($reference);

            $this->mail->toUser(MailService::SUPPORT, $reporter, $subject, 'report-decided', $context);
        }

        if (in_array($event->getDecision(), self::ESTABLISHMENT_DECISIONS, true)) {
            $subject = MailSubject::PRO_REPORT_DECIDED->text($reference);
            $context['audience'] = 'establishment';

            $this->sendToEstablishment($report, $subject, 'pro-report-decided', $context);
        }
    }

    private function confirmToEstablishment(?User $reporter, array $context): void
    {
        if ($reporter === null) {
            return;
        }

        $subject = MailSubject::PRO_REPORT_SENT->text($context['reference']);

        $this->mail->toUser(MailService::PRO, $reporter, $subject, 'pro-report-sent', [
            'report' => $context,
            'reportsUrl' => $this->mail->url('pro_reports'),
        ]);
    }

    private function forwardToEstablishment(Report $report, array $context): void
    {
        if ($report->getDirection() !== ReportDirection::FROM_CUSTOMER) {
            return;
        }

        $subject = MailSubject::PRO_REPORT_MESSAGE->text($context['report']['reference']);

        $this->sendToEstablishment($report, $subject, 'pro-report-message', $context);
    }

    private function sendDecisionToReporter(?User $reporter, string $reference, array $context): void
    {
        if ($reporter === null) {
            return;
        }

        $subject = MailSubject::PRO_REPORT_DECIDED->text($reference);
        $context['audience'] = 'reporter';

        $this->mail->toUser(MailService::PRO, $reporter, $subject, 'pro-report-decided', $context);
    }

    private function sendToEstablishment(Report $report, string $subject, string $template, array $context): void
    {
        $recipients = $this->recipients->establishment($report->getEstablishment());

        $this->mail->send(MailService::PRO, $recipients, $subject, $template, $context);
    }

    private function isPublic(ReportMessage $message, Report $report): bool
    {
        return !$message->isInternal() && ReportStatusHelper::isOpen($report);
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

    private function dateTime(?DateTimeImmutable $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return MailText::DATE_AT_TIME->text(CalendarHelper::longDate($date), DateHelper::time($date));
    }
}