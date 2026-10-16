<?php

namespace App\Establishment\Provider;

use App\Entity\Booking;
use App\Entity\Website\WebsitePlan;
use App\Repository\BookingRepository;
use App\Shared\Helper\DurationHelper;
use App\Shared\Helper\MoneyHelper;
use App\Website\Provider\WebsiteProvider;
use App\Website\Enum\PlanBilling;
use DateTimeImmutable;

class HomeProvider
{
    public const STEPS = [
        ['n' => '1', 'title' => 'Un pro libère un créneau', 'text' => 'Un client annule ? Le pro publie l’horaire libéré avec une remise, en quelques secondes.'],
        ['n' => '2', 'title' => 'Tu le réserves en deux clics', 'text' => 'Tu reçois une alerte si c’est près de toi. Le créneau est bloqué à ton nom dès la réservation.'],
        ['n' => '3', 'title' => 'Tu paies sur place', 'text' => 'Pas de prépaiement : tu règles le prix réduit directement chez le pro, comme d’habitude.'],
    ];

    private ?array $facts = null;

    public function __construct(
        private WebsiteProvider $website,
        private BookingRepository $bookings,
    ) {
    }

    public function proFacts(): array
    {
        return $this->facts ??= [
            ['value' => '30 s', 'label' => 'pour publier un créneau libéré'],
            ['value' => '0 €', 'label' => 'si personne ne réserve'],
            ['value' => $this->commission(), 'label' => 'de commission par réservation'],
            ['value' => $this->averageDelay(), 'label' => 'en moyenne pour trouver preneur'],
        ];
    }

    private function commission(): ?string
    {
        $prices = array_map(
            static fn (WebsitePlan $plan): int => $plan->getPrice(),
            array_filter($this->website->getPlans(), static fn (WebsitePlan $plan): bool => $plan->getBilling() === PlanBilling::PER_BOOKING),
        );

        return $prices !== [] ? MoneyHelper::format(min($prices)) : null;
    }

    private function averageDelay(): ?string
    {
        $delays = [];

        foreach ($this->bookings->findKeptSince((new DateTimeImmutable())->modify('-30 days')) as $booking) {
            $publishedAt = $booking instanceof Booking ? $booking->getSlot()?->getCreatedAt() : null;

            if ($publishedAt !== null && $booking->getCreatedAt() !== null) {
                $delays[] = max(0, $booking->getCreatedAt()->getTimestamp() - $publishedAt->getTimestamp());
            }
        }

        return $delays !== [] ? DurationHelper::format(max(1, (int) round(array_sum($delays) / count($delays) / 60))) : null;
    }
}