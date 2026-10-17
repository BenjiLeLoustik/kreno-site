<?php

namespace App\Review\Provider;

use App\Booking\Enum\BookingStatus;
use App\Booking\Provider\CustomerBookingsProvider;
use App\Entity\Booking;
use App\Entity\User\User;
use App\Repository\BookingRepository;
use App\Repository\Establishment\EstablishmentReviewRepository;
use App\Review\Enum\ReviewRating;
use App\Review\Presenter\ReviewPresenter;
use App\Review\Service\ReviewService;
use App\Shared\Helper\DateHelper;
use DateTimeImmutable;

class CustomerReviewPageProvider
{
    public function __construct(
        private BookingRepository $bookings,
        private EstablishmentReviewRepository $reviews,
        private CustomerBookingsProvider $customerBookings,
        private ReviewService $reviewService,
        private ReviewPresenter $presenter,
    ) {
    }

    public function index(User $user): array
    {
        $reviews = $this->reviews->findForCustomer($user);

        return [
            'pending' => $this->pending($user, $this->reviewedIds($reviews)),
            'reviews' => $this->presenter->customerList($reviews),
            'windowDays' => ReviewService::WINDOW_DAYS,
        ];
    }

    public function booking(string $reference, User $user): Booking
    {
        return $this->customerBookings->owned($reference, $user);
    }

    public function form(string $reference, User $user): array
    {
        $booking = $this->booking($reference, $user);
        $this->reviewService->assertReviewable($booking);

        return [
            'booking' => $this->bookingSummary($booking),
            'ratings' => ReviewRating::choices(),
        ];
    }

    public function pendingCount(User $user): int
    {
        return count($this->pending($user, $this->reviewedIds($this->reviews->findForCustomer($user))));
    }

    private function pending(User $user, array $reviewedIds): array
    {
        $now = new DateTimeImmutable();
        $criteria = ['customer' => $user, 'status' => BookingStatus::HONORED];
        $pending = [];

        foreach ($this->bookings->findBy($criteria, ['honoredAt' => 'DESC']) as $booking) {
            if ($this->isPending($booking, $reviewedIds, $now)) {
                $pending[] = $this->bookingSummary($booking);
            }
        }

        return $pending;
    }

    private function isPending(Booking $booking, array $reviewedIds, DateTimeImmutable $now): bool
    {
        if (in_array($booking->getId(), $reviewedIds, true)) {
            return false;
        }

        return $this->reviewService->withinWindow($booking, $now);
    }

    private function reviewedIds(array $reviews): array
    {
        $ids = [];

        foreach ($reviews as $review) {
            $ids[] = $review->getBooking()?->getId();
        }

        return $ids;
    }

    private function bookingSummary(Booking $booking): array
    {
        $slot = $booking->getSlot();

        return [
            'reference' => $booking->getReference(),
            'establishment' => $slot?->getEstablishment()?->getName(),
            'service' => $slot?->getService()?->getName(),
            'startsAt' => DateHelper::iso($slot?->getStartsAt()),
            'deadline' => DateHelper::iso($this->reviewService->deadline($booking)),
        ];
    }
}