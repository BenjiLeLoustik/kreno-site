<?php

namespace App\Establishment\Provider;

use App\Catalog\Presenter\CityPresenter;
use App\Entity\Booking;
use App\Entity\City;
use App\Establishment\Dto\SlotSearchInput;
use App\Establishment\Enum\HomeStep;
use App\Establishment\Presenter\SlotPresenter;
use App\Establishment\Service\SlotSearchService;
use App\Repository\BookingRepository;
use App\Shared\Helper\DurationHelper;
use App\Shared\Helper\MoneyHelper;
use App\Shared\Helper\Translation;
use App\Website\Enum\PlanBilling;
use App\Website\Provider\WebsiteProvider;
use DateTimeImmutable;

class HomeProvider
{
    public const DELAY_DAYS = 30;

    public const HOME_SLOTS = 6;

    private ?array $facts = null;

    public function __construct(
        private WebsiteProvider $website,
        private BookingRepository $bookings,
        private SlotSearchService $search,
        private SlotPresenter $slotPresenter,
        private CityPresenter $cityPresenter,
    ) {
    }

    public static function steps(): array
    {
        $steps = [];

        foreach (HomeStep::cases() as $step) {
            $steps[] = [
                'n' => (string) $step->value,
                'title' => $step->title(),
                'text' => $step->text()
            ];
        }

        return $steps;
    }

    public function page(string $city): array
    {
        $now = new DateTimeImmutable();
        $input = new SlotSearchInput();
        $input->limit = self::HOME_SLOTS;

        if ($city !== '') {
            $input->where = $city;
        }

        $result = $this->search->search($input, $now);

        return [
            'city' => $this->city($result->getCity()),
            'slots' => $this->slotPresenter->cards($result->getMatches(), $now),
            'steps' => self::steps(),
            'proFacts' => $this->proFacts(),
        ];
    }

    public function proFacts(): array
    {
        return $this->facts ??= [
            [
                'value' => '30 s',
                'label' => Translation::translate('pour publier un créneau libéré'),
            ],
            [
                'value' => '0 €',
                'label' => Translation::translate('si personne ne réserve'),
            ],
            [
                'value' => $this->commission(),
                'label' => Translation::translate('de commission par réservation'),
            ],
            [
                'value' => $this->averageDelay(),
                'label' => Translation::translate('en moyenne pour trouver preneur'),
            ],
        ];
    }

    private function city(?City $city): ?array
    {
        if ($city === null) {
            return null;
        }

        return $this->cityPresenter->summary($city);
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