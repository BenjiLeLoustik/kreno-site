<?php

namespace App\Review\Presenter;

use App\Entity\Establishment\EstablishmentReview;
use App\Entity\User\User;
use App\Review\Enum\ReviewRating;
use App\Review\Enum\ReviewText;
use App\Shared\Helper\DateHelper;
use App\User\Helper\UserNameHelper;

class ReviewPresenter
{
    public function forPublic(EstablishmentReview $review): array
    {
        return [
            'id' => $review->getId(),
            'author' => $this->author($review),
            'rating' => $review->getRating(),
            'ratingLabel' => ReviewRating::labelOf($review->getRating()),
            'comment' => $review->getComment(),
            'service' => $review->getBooking()?->getSlot()?->getService()?->getName(),
            'createdAt' => DateHelper::iso($review->getCreatedAt()),
            'reply' => $review->getReply(),
            'repliedAt' => DateHelper::iso($review->getRepliedAt()),
        ];
    }

    public function forCustomer(EstablishmentReview $review): array
    {
        $establishment = $review->getEstablishment();

        return $this->forPublic($review) + [
                'establishment' => $establishment?->getName(),
                'city' => $establishment?->getCity()?->getSlug(),
                'slug' => $establishment?->getSlug(),
                'status' => $review->getStatus()->value,
                'statusLabel' => $review->getStatus()->label(),
                'visible' => $review->getStatus()->isVisible(),
                'moderationNote' => $review->getModerationNote(),
            ];
    }

    public function forProfessional(EstablishmentReview $review): array
    {
        return $this->forPublic($review) + [
                'reference' => $review->getBooking()?->getReference(),
                'status' => $review->getStatus()->value,
                'statusLabel' => $review->getStatus()->proLabel(),
                'visible' => $review->getStatus()->isVisible(),
                'reportReason' => $review->getReportReason(),
                'moderationNote' => $review->getModerationNote(),
            ];
    }

    public function forAdmin(EstablishmentReview $review): array
    {
        return $this->forProfessional($review) + [
                'establishment' => $review->getEstablishment()?->getName(),
                'customerEmail' => $review->getCustomer()?->getEmail(),
                'statusLabel' => $review->getStatus()->label(),
                'tone' => $review->getStatus()->tone(),
                'reportedAt' => DateHelper::iso($review->getReportedAt()),
                'reportedBy' => $this->name($review->getReportedBy()),
                'moderatedAt' => DateHelper::iso($review->getModeratedAt()),
                'moderatedBy' => $this->name($review->getModeratedBy()),
            ];
    }

    public function summary(array $distribution): array
    {
        $count = array_sum($distribution);
        $average = $this->average($distribution, $count);

        return [
            'count' => $count,
            'countLabel' => ReviewText::COUNT->text($count),
            'average' => $average,
            'averageLabel' => number_format($average, 1, ',', ''),
            'bars' => $this->bars($distribution, $count),
        ];
    }

    public function publicList(array $reviews): array
    {
        return array_map(fn (EstablishmentReview $review): array => $this->forPublic($review), $reviews);
    }

    public function customerList(array $reviews): array
    {
        return array_map(fn (EstablishmentReview $review): array => $this->forCustomer($review), $reviews);
    }

    public function professionalList(array $reviews): array
    {
        return array_map(fn (EstablishmentReview $review): array => $this->forProfessional($review), $reviews);
    }

    public function adminList(array $reviews): array
    {
        return array_map(fn (EstablishmentReview $review): array => $this->forAdmin($review), $reviews);
    }

    private function average(array $distribution, int $count): float
    {
        if ($count === 0) {
            return 0.0;
        }

        $total = 0;

        foreach ($distribution as $rating => $reviews) {
            $total += $rating * $reviews;
        }

        return round($total / $count, 1);
    }

    private function bars(array $distribution, int $count): array
    {
        $bars = [];

        foreach (array_reverse(ReviewRating::cases()) as $rating) {
            $reviews = $distribution[$rating->value] ?? 0;
            $bars[] = [
                'rating' => $rating->value,
                'count' => $reviews,
                'percent' => $this->percent($reviews, $count)
            ];
        }

        return $bars;
    }

    private function percent(int $reviews, int $count): int
    {
        if ($count === 0) {
            return 0;
        }

        return (int) round($reviews * 100 / $count);
    }

    private function author(EstablishmentReview $review): string
    {
        $customer = $review->getCustomer();

        if ($customer === null) {
            return ReviewText::ANONYMOUS->text();
        }

        return UserNameHelper::shortName($customer);
    }

    private function name(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        return UserNameHelper::shortName($user);
    }
}