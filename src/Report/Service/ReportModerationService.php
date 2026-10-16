<?php

namespace App\Report\Service;

use App\Entity\Report\Report;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Service\EstablishmentAdminService;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Report\Dto\ReportDecisionInput;
use App\Report\Enum\ReportDecision;
use App\Report\Enum\ReportDirection;
use App\Report\Enum\ReportStatus;
use App\Report\Enum\ReportText;
use App\Report\Event\ReportDecidedEvent;
use App\Report\Exception\EstablishmentNotOnlineException;
use App\Report\Exception\NoCustomerToSuspendException;
use App\Report\Exception\NoEstablishmentToSuspendException;
use App\Report\Exception\ReportAlreadyClosedException;
use App\Report\Helper\ReportStatusHelper;
use App\User\Enum\NotificationType;
use App\User\Service\NotificationService;
use App\User\Service\SanctionService;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class ReportModerationService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private EventManagerInterface $events,
        private ActivityLogger $logger,
        private ReportService $reports,
        private SanctionService $sanctions,
        private EstablishmentAdminService $establishments,
        private NotificationService $notifications,
    ) {
    }

    public function review(Report $report, User $admin): Report
    {
        if ($report->getStatus() === ReportStatus::SENT) {
            $report->setStatus(ReportStatus::IN_REVIEW);
        }

        $report->setHandledBy($admin);
        $this->orm->flush();

        return $report;
    }

    public function note(Report $report, User $admin, string $body): Report
    {
        $this->reports->post($report, $admin, $body, true);
        $report->setHandledBy($admin);
        $this->orm->flush();

        return $report;
    }

    public function decide(Report $report, User $admin, ReportDecisionInput $input): Report
    {
        if (!ReportStatusHelper::isOpen($report)) {
            throw new ReportAlreadyClosedException();
        }

        $decision = ReportDecision::tryFrom((string) $input->decision) ?? ReportDecision::RESOLVE;
        $message = trim((string) $input->message);
        $resolution = $decision->resolution($report->getDirection(), $input->days);

        if ($decision === ReportDecision::SUSPEND) {
            $this->suspendTarget($report, $admin, $message, $input->days);
        }

        $this->reports->post($report, $admin, $message);
        $report->setStatus($decision->status());
        $report->setResolution($resolution);
        $report->setHandledBy($admin);
        $report->setHandledAt(new DateTimeImmutable());

        $this->log($report, $admin, (string) $input->decision, $resolution, $message);
        $this->notifyReporter($report, $resolution, $message);

        $this->orm->flush();
        $this->events->dispatch(new ReportDecidedEvent($report, (string) $input->decision));

        return $report;
    }

    private function suspendTarget(Report $report, User $admin, string $reason, int $days): void
    {
        if ($report->getDirection() === ReportDirection::FROM_CUSTOMER) {
            $this->suspendEstablishment($report, $admin, $reason);

            return;
        }

        $user = $report->getReportedUser();

        if ($user === null) {
            throw new NoCustomerToSuspendException();
        }

        $this->sanctions->suspend($user, $days, $reason, $admin);
    }

    private function suspendEstablishment(Report $report, User $admin, string $reason): void
    {
        $establishment = $report->getEstablishment();

        if ($establishment === null) {
            throw new NoEstablishmentToSuspendException();
        }

        if ($establishment->getStatus() !== EstablishmentStatus::ONLINE) {
            throw new EstablishmentNotOnlineException();
        }

        $this->establishments->suspend($establishment, $admin, $reason);
    }

    private function log(Report $report, User $admin, string $decision, string $resolution, string $message): void
    {
        $text = sprintf('Signalement %s : %s', ReportStatusHelper::reference($report), $resolution);
        $context = [
            'decision' => $decision,
            'message' => $message
        ];

        $this->logger->log(ActivityType::MODERATION, $text, $admin, null, $report, $context);
    }

    private function notifyReporter(Report $report, string $resolution, string $message): void
    {
        $reporter = $report->getReporter();

        if ($reporter === null) {
            return;
        }

        $title = ReportText::DECISION_NOTIFICATION->text(
            ReportStatusHelper::reference($report),
            mb_strtolower($resolution),
        );

        $this->notifications->notify($reporter, NotificationType::REPORT, $title, $message, null, false);
    }
}