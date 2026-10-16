<?php

namespace App\Establishment\Presenter;

use App\Company\Presenter\EstablishmentSummaryPresenter;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentImage;
use App\Entity\Establishment\EstablishmentService;
use App\Entity\User\User;
use App\Establishment\Helper\GeoHelper;
use App\Establishment\Helper\ServiceSuggestionHelper;
use App\Repository\Establishment\EstablishmentClosureRepository;
use App\Repository\Establishment\EstablishmentFollowerRepository;
use App\Repository\Establishment\EstablishmentSlotRepository;
use App\Shared\Helper\MoneyHelper;
use DateTimeImmutable;

class EstablishmentPagePresenter
{
    public function __construct(
        private EstablishmentSummaryPresenter $summaryPresenter,
        private ServicePresenter $servicePresenter,
        private ImagePresenter $imagePresenter,
        private OpeningHoursPresenter $hoursPresenter,
        private SlotPresenter $slotPresenter,
        private EstablishmentSlotRepository $slots,
        private EstablishmentClosureRepository $closures,
        private EstablishmentFollowerRepository $followers,
    ) {
    }

    public function publicPage(CompanyEstablishment $establishment, ?User $viewer = null, ?float $latitude = null, ?float $longitude = null, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $hours = $establishment->getOpeningHours()->toArray();
        $distance = $latitude !== null && $longitude !== null && $establishment->getLatitude() !== null && $establishment->getLongitude() !== null
            ? GeoHelper::distanceKm($latitude, $longitude, (float) $establishment->getLatitude(), (float) $establishment->getLongitude())
            : null;

        return $this->summaryPresenter->summary($establishment) + [
                'description' => $establishment->getDescription(),
                'images' => $this->imagePresenter->summaries($establishment->getImages()->toArray()),
                'contact' => [
                    'phone' => $establishment->getPhone(),
                    'email' => $establishment->getEmail(),
                    'website' => $establishment->getWebsite(),
                    'websiteLabel' => $establishment->getWebsite() !== null ? preg_replace('#^https?://(www\.)?#', '', rtrim($establishment->getWebsite(), '/')) : null,
                ],
                'location' => [
                    'addressLine1' => $establishment->getAddressLine1(),
                    'addressLine2' => $establishment->getAddressLine2(),
                    'postalCode' => $establishment->getPostalCode(),
                    'locality' => $establishment->getLocality(),
                    'latitude' => $establishment->getLatitude() !== null ? (float) $establishment->getLatitude() : null,
                    'longitude' => $establishment->getLongitude() !== null ? (float) $establishment->getLongitude() : null,
                    'distance' => GeoHelper::format($distance),
                ],
                'services' => $this->servicePresenter->summaries(array_filter($establishment->getServices()->toArray(), static fn (EstablishmentService $service): bool => $service->isActive())),
                'hours' => $this->hoursPresenter->grouped($hours),
                'openNow' => $this->hoursPresenter->status($hours, $now),
                'closures' => $this->hoursPresenter->closures($this->closures->findUpcoming($establishment, $now)),
                'slots' => $this->slotPresenter->cards($this->slots->findAvailableForEstablishment($establishment, $now, 12), $now),
                'followersCount' => $this->followers->countForEstablishment($establishment),
                'following' => $viewer !== null && $this->followers->isFollowing($establishment, $viewer),
            ];
    }

    public function card(CompanyEstablishment $establishment, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $cover = $establishment->getImages()->first();

        return $this->summaryPresenter->summary($establishment) + [
                'cover' => $cover instanceof EstablishmentImage ? $this->imagePresenter->url($cover) : null,
                'description' => $establishment->getDescription() !== null ? mb_strimwidth($establishment->getDescription(), 0, 140, '…') : null,
                'openNow' => $this->hoursPresenter->status($establishment->getOpeningHours()->toArray(), $now),
                'availableSlots' => count($this->slots->findAvailableForEstablishment($establishment, $now, 50)),
            ];
    }

    public function settings(CompanyEstablishment $establishment, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();

        return $this->summaryPresenter->forCompany($establishment) + [
                'form' => [
                    'name' => $establishment->getName(),
                    'categorySlug' => $establishment->getCategory()?->getSlug(),
                    'citySlug' => $establishment->getCity()?->getSlug(),
                    'description' => $establishment->getDescription(),
                    'addressLine1' => $establishment->getAddressLine1(),
                    'addressLine2' => $establishment->getAddressLine2(),
                    'postalCode' => $establishment->getPostalCode(),
                    'locality' => $establishment->getLocality(),
                    'latitude' => $establishment->getLatitude() !== null ? (float) $establishment->getLatitude() : null,
                    'longitude' => $establishment->getLongitude() !== null ? (float) $establishment->getLongitude() : null,
                    'phone' => $establishment->getPhone(),
                    'email' => $establishment->getEmail(),
                    'website' => $establishment->getWebsite(),
                    'defaultDiscount' => $establishment->getDefaultDiscount(),
                ],
                'services' => $this->servicePresenter->summaries($establishment->getServices()->toArray()),
                'images' => $this->imagePresenter->summaries($establishment->getImages()->toArray()),
                'hours' => $this->hoursPresenter->week($establishment->getOpeningHours()->toArray()),
                'closures' => $this->hoursPresenter->closures($this->closures->findUpcoming($establishment, $now)),
                'followersCount' => $this->followers->countForEstablishment($establishment),
            ];
    }

    public function signupServices(CompanyEstablishment $establishment, int $defaultDiscount): array
    {
        $services = $this->servicePresenter->summaries(array_filter($establishment->getServices()->toArray(), static fn (EstablishmentService $service): bool => $service->isActive()));
        $discount = $establishment->getDefaultDiscount() ?? $defaultDiscount;
        $example = $services[0] ?? null;

        return [
            'services' => $services,
            'suggestions' => ServiceSuggestionHelper::for($establishment->getCategory()?->getSlug()),
            'categoryName' => $establishment->getCategory()?->getName(),
            'durations' => ServiceSuggestionHelper::DURATIONS,
            'defaultDiscount' => $discount,
            'example' => $example === null ? null : [
                'name' => $example['name'],
                'price' => $example['priceLabel'],
                'discounted' => MoneyHelper::format(MoneyHelper::applyDiscount($example['price'], $discount)),
            ],
        ];
    }

    public function signupPage(CompanyEstablishment $establishment, ?DateTimeImmutable $now = null): array
    {
        $hours = $establishment->getOpeningHours()->toArray();

        return [
            'description' => $establishment->getDescription(),
            'descriptionMax' => 400,
            'images' => $this->imagePresenter->summaries($establishment->getImages()->toArray()),
            'imagesMax' => 6,
            'hours' => $this->hoursPresenter->week($hours),
            'preview' => [
                'name' => $establishment->getName(),
                'category' => $establishment->getCategory()?->getName(),
                'locality' => $establishment->getLocality(),
                'openShort' => $this->hoursPresenter->status($hours, $now)['label'],
                'description' => $establishment->getDescription() !== null ? mb_strimwidth($establishment->getDescription(), 0, 120, '…') : null,
            ],
        ];
    }
}