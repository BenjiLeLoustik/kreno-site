<?php

namespace App\Review\Service;

use App\Entity\Establishment\EstablishmentReview;
use App\Entity\User\User;
use App\Review\Enum\ReviewStatus;
use App\Review\Exception\ReviewAlreadyReportedException;
use App\Review\Exception\ReviewHiddenException;
use DateTimeImmutable;
use NeoPHP\Package\Orm\OrmManagerInterface;

class ProReviewService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private ReviewNotifier $notifier,
    ) {
    }

    public function reply(EstablishmentReview $review, User $author, string $body): void
    {
        $this->assertNotHidden($review);
        $firstReply = $review->getReply() === null;

        $review
            ->setReply(trim($body))
            ->setRepliedAt(new DateTimeImmutable())
            ->setRepliedBy($author);

        if ($firstReply) {
            $this->notifier->replied($review);
        }

        $this->orm->flush();
    }

    public function report(EstablishmentReview $review, User $author, string $reason): void
    {
        $this->assertNotHidden($review);

        if ($review->getStatus() === ReviewStatus::REPORTED) {
            throw new ReviewAlreadyReportedException();
        }

        $review
            ->setStatus(ReviewStatus::REPORTED)
            ->setReportReason(trim($reason))
            ->setReportedAt(new DateTimeImmutable())
            ->setReportedBy($author);

        $this->orm->flush();
    }

    private function assertNotHidden(EstablishmentReview $review): void
    {
        if ($review->getStatus() === ReviewStatus::HIDDEN) {
            throw new ReviewHiddenException();
        }
    }
}