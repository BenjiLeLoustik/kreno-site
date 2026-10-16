<?php

namespace App\Company\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class CompanyInput
{
    #[Assert\Length(min: 2, max: 120)]
    public ?string $name = null;

    #[Assert\Length(max: 160)]
    public ?string $legalName = null;

    #[Assert\Regex(pattern: '/^[\d ]{9,11}$/')]
    public ?string $siren = null;

    #[Assert\Regex(pattern: '/^[A-Z]{2}[0-9A-Z]{2,13}$/')]
    public ?string $vatNumber = null;

    #[Assert\Length(max: 160)]
    public ?string $addressLine1 = null;

    #[Assert\Regex(pattern: '/^\d{5}$/')]
    public ?string $postalCode = null;

    #[Assert\Length(max: 100)]
    public ?string $locality = null;

    #[Assert\Range(min: 0, max: 80)]
    public ?int $defaultDiscount = null;

    #[Assert\Length(max: 160)]
    public ?string $billingName = null;

    #[Assert\Email]
    public ?string $billingEmail = null;

    public ?bool $bookingEmailNotifications = null;

    public ?bool $monthlyTips = null;
}