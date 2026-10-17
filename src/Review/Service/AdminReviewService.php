<?php

namespace App\Review\Service;

use App\Entity\Establishment\EstablishmentReview;
use App\Entity\User\User;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Review\Enum\ReviewStatus;
use App\Review\Exception\ReviewAlreadyHiddenException;
use App\Review\Exception\ReviewAlreadyPublishedException;
use DateTimeImmutable;
use NeoPHP\Package\Orm\OrmManagerInterface;

class AdminReviewService
{
    public const LOG_HIDDEN = 'Avis %d/5 masqué sur %s';

    public const LOG_PUBLISHED = 'Avis %d/5 remis en ligne sur %s';

    public function __construct(
        private OrmManagerInterface $orm,
        private ActivityLogger $logger,
        private ReviewNotifier $notifier,
    ) {
    }

    public function hide(EstablishmentReview $review, User $admin, string $reason): void
    {
        if ($review->getStatus() === ReviewStatus::HIDDEN) {
            throw new ReviewAlreadyHiddenException();
        }

        $reported = $review->getStatus() === ReviewStatus::REPORTED;
        $this->moderate($review, ReviewStatus::HIDDEN, $admin)->setModerationNote(trim($reason));
        $this->log(self::LOG_HIDDEN, $review, $admin, ['reason' => trim($reason)]);
        $this->notifier->hidden($review, $reported);
        $this->orm->flush();
    }

    public function publish(EstablishmentReview $review, User $admin): void
    {
        if ($review->getStatus() === ReviewStatus::PUBLISHED) {
            throw new ReviewAlreadyPublishedException();
        }

        $reported = $review->getStatus() === ReviewStatus::REPORTED;
        $this->moderate($review, ReviewStatus::PUBLISHED, $admin)->setModerationNote(null);
        $this->log(self::LOG_PUBLISHED, $review, $admin);

        if ($reported) {
            $this->notifier->kept($review);
        }

        $this->orm->flush();
    }

    private function moderate(EstablishmentReview $review, ReviewStatus $status, User $admin): EstablishmentReview
    {
        return $review
            ->setStatus($status)
            ->setModeratedAt(new DateTimeImmutable())
            ->setModeratedBy($admin);
    }

    private function log(string $pattern, EstablishmentReview $review, User $admin, array $changes = []): void
    {
        $message = sprintf($pattern, $review->getRating(), $review->getEstablishment()?->getName());

        $this->logger->log(ActivityType::MODERATION, $message, $admin, null, $review, $changes);
    }
}