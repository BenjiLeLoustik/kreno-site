<?php

namespace App\Review\Service;

use App\Booking\Enum\BookingStatus;
use App\Entity\Booking;
use App\Entity\Establishment\EstablishmentReview;
use App\Entity\User\User;
use App\Repository\Establishment\EstablishmentReviewRepository;
use App\Review\Dto\ReviewInput;
use App\Review\Exception\BookingAlreadyReviewedException;
use App\Review\Exception\BookingNotReviewableException;
use App\Review\Exception\ReviewWindowClosedException;
use DateTimeImmutable;
use NeoPHP\Package\Orm\OrmManagerInterface;

class ReviewService
{
    public const WINDOW_DAYS = 30;

    public function __construct(
        private OrmManagerInterface $orm,
        private EstablishmentReviewRepository $reviews,
        private ReviewNotifier $notifier,
    ) {
    }

    public function create(Booking $booking, User $customer, ReviewInput $input): EstablishmentReview
    {
        $this->assertReviewable($booking);

        $review = (new EstablishmentReview())
            ->setEstablishment($booking->getSlot()?->getEstablishment())
            ->setBooking($booking)
            ->setCustomer($customer)
            ->setRating((int) $input->rating)
            ->setComment($this->comment($input->comment));

        $this->orm->persist($review);
        $this->notifier->received($review);
        $this->orm->flush();

        return $review;
    }

    public function assertReviewable(Booking $booking): void
    {
        if ($booking->getStatus() !== BookingStatus::HONORED) {
            throw new BookingNotReviewableException();
        }

        if ($this->reviews->findOneForBooking($booking) !== null) {
            throw new BookingAlreadyReviewedException();
        }

        if (!$this->withinWindow($booking, new DateTimeImmutable())) {
            throw new ReviewWindowClosedException(self::WINDOW_DAYS);
        }
    }

    public function withinWindow(Booking $booking, DateTimeImmutable $now): bool
    {
        $deadline = $this->deadline($booking);

        if ($deadline === null) {
            return false;
        }

        return $now <= $deadline;
    }

    public function deadline(Booking $booking): ?DateTimeImmutable
    {
        $reference = $booking->getHonoredAt() ?? $booking->getSlot()?->getStartsAt();

        return $reference?->modify(sprintf('+%d days', self::WINDOW_DAYS));
    }

    private function comment(?string $comment): ?string
    {
        $comment = trim((string) $comment);

        if ($comment === '') {
            return null;
        }

        return $comment;
    }
}