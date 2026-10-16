<?php

namespace App\Establishment\Provider;

use App\Entity\Booking;
use App\Establishment\Enum\HomeStep;
use App\Repository\BookingRepository;
use App\Shared\Helper\DurationHelper;
use App\Shared\Helper\MoneyHelper;
use App\Website\Enum\PlanBilling;
use App\Website\Provider\WebsiteProvider;
use DateTimeImmutable;

class HomeProvider
{
    public const DELAY_DAYS = 30;

    private ?array $facts = null;

    public function __construct(
        private WebsiteProvider $website,
        private BookingRepository $bookings,
    ) {
    }

    public static function steps(): array
    {
        $steps = [];

        foreach (HomeStep::cases() as $step) {
            $steps[] = ['n' => (string) $step->value, 'title' => $step->title(), 'text' => $step->text()];
        }

        return $steps;
    }

    public function proFacts(): array
    {
        return $this->facts ??= [
            [
                'value' => '30 s',
                'label' => 'pour publier un créneau libéré'
            ],
            [
                'value' => '0 €',
                'label' => 'si personne ne réserve'
            ],
            [
                'value' => $this->commission(),
                'label' => 'de commission par réservation'
            ],
            [
                'value' => $this->averageDelay(),
                'label' => 'en moyenne pour trouver preneur'
            ],
        ];
    }

    private function commission(): ?string
    {
        $prices = [];

        foreach ($this->website->getPlans() as $plan) {
            if ($plan->getBilling() === PlanBilling::PER_BOOKING) {
                $prices[] = $plan->getPrice();
            }
        }

        if ($prices === []) {
            return null;
        }

        return MoneyHelper::format(min($prices));
    }

    private function averageDelay(): ?string
    {
        $delays = [];
        $since = (new DateTimeImmutable())->modify(sprintf('-%d days', self::DELAY_DAYS));

        foreach ($this->bookings->findKeptSince($since) as $booking) {
            $delay = $this->delay($booking);

            if ($delay !== null) {
                $delays[] = $delay;
            }
        }

        if ($delays === []) {
            return null;
        }

        $minutes = (int) round(array_sum($delays) / count($delays) / 60);

        return DurationHelper::format(max(1, $minutes));
    }

    private function delay(mixed $booking): ?int
    {
        if (!$booking instanceof Booking) {
            return null;
        }

        $publishedAt = $booking->getSlot()?->getCreatedAt();
        $bookedAt = $booking->getCreatedAt();

        if ($publishedAt === null || $bookedAt === null) {
            return null;
        }

        return max(0, $bookedAt->getTimestamp() - $publishedAt->getTimestamp());
    }
}