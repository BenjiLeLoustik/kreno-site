<?php

namespace App\Website\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class WebsiteRuleInput
{
    #[Assert\Range(min: 0, max: 1440)]
    public ?int $freeCancellationMinutes = null;

    #[Assert\Range(min: 0, max: 120)]
    public ?int $bookingLeadMinutes = null;

    #[Assert\Range(min: 1, max: 10)]
    public ?int $maxUpcomingBookingsPerUser = null;

    #[Assert\Range(min: 1, max: 10)]
    public ?int $toleratedNoShows = null;

    #[Assert\Range(min: 7, max: 365)]
    public ?int $noShowWindowDays = null;

    #[Assert\Range(min: 1, max: 365)]
    public ?int $suspensionDays = null;

    #[Assert\Range(min: 0, max: 80)]
    public ?int $minDiscount = null;

    #[Assert\Range(min: 0, max: 80)]
    public ?int $maxDiscount = null;

    #[Assert\Range(min: 0, max: 80)]
    public ?int $defaultDiscount = null;

    #[Assert\Range(min: 1, max: 30)]
    public ?int $publicationWindowDays = null;

    #[Assert\Range(min: 0, max: 120)]
    public ?int $autoRemovalMinutes = null;

    public function toArray(): array
    {
        return array_filter(get_object_vars($this), static fn (?int $value): bool => $value !== null);
    }
}