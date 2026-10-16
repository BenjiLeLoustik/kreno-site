<?php

namespace App\Report\Service;

use App\Entity\Report\Report;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Service\EstablishmentAdminService;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Report\Dto\ReportDecisionInput;
use App\Report\Enum\ReportDirection;
use App\Report\Enum\ReportStatus;
use App\Report\Event\ReportDecidedEvent;
use App\Report\Helper\ReportStatusHelper;
use App\Shared\Exception\BusinessRuleException;
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
            throw new BusinessRuleException('Ce signalement est déjà clos.');
        }

        $message = trim((string) $input->message);
        $fromCustomer = $report->getDirection() === ReportDirection::FROM_CUSTOMER;

        [$status, $resolution] = match ($input->decision) {
            'dismiss' => [ReportStatus::DISMISSED, 'Classé sans suite'],
            'warn' => [ReportStatus::RESOLVED, 'Avertissement envoyé'],
            'suspend' => [ReportStatus::RESOLVED, $fromCustomer ? 'Établissement suspendu' : sprintf('Compte client suspendu %d jours', $input->days)],
            default => [ReportStatus::RESOLVED, 'Traité'],
        };

        if ($input->decision === 'suspend') {
            $this->suspendTarget($report, $admin, $message, $input->days);
        }

        $this->reports->post($report, $admin, $message);
        $report->setStatus($status);
        $report->setResolution($resolution);
        $report->setHandledBy($admin);
        $report->setHandledAt(new DateTimeImmutable());

        $this->logger->log(ActivityType::MODERATION, sprintf('Signalement %s : %s', ReportStatusHelper::reference($report), $resolution), $admin, null, $report, ['decision' => $input->decision, 'message' => $message]);

        if ($report->getReporter() !== null) {
            $this->notifications->notify($report->getReporter(), NotificationType::REPORT, sprintf('Signalement %s : %s', ReportStatusHelper::reference($report), mb_strtolower($resolution)), $message, null, false);
        }

        $this->orm->flush();
        $this->events->dispatch(new ReportDecidedEvent($report, (string) $input->decision));

        return $report;
    }

    private function suspendTarget(Report $report, User $admin, string $reason, int $days): void
    {
        if ($report->getDirection() === ReportDirection::FROM_CUSTOMER) {
            $establishment = $report->getEstablishment() ?? throw new BusinessRuleException('Aucun établissement à suspendre.');

            if ($establishment->getStatus() !== EstablishmentStatus::ONLINE) {
                throw new BusinessRuleException('Cet établissement n\'est pas en ligne.');
            }

            $this->establishments->suspend($establishment, $admin, $reason);

            return;
        }

        $user = $report->getReportedUser() ?? throw new BusinessRuleException('Aucun client à suspendre.');
        $this->sanctions->suspend($user, $days, $reason, $admin);
    }
}