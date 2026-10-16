<?php

namespace App\Company\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class EstablishmentInput
{
    #[Assert\Length(min: 2, max: 120)]
    public ?string $name = null;

    #[Assert\Regex(pattern: '/^[\d ]{14,17}$/', message: 'Le SIRET contient 14 chiffres.')]
    public ?string $siret = null;

    public ?string $categorySlug = null;

    public ?string $citySlug = null;

    #[Assert\Length(max: 160)]
    public ?string $addressLine1 = null;

    #[Assert\Length(max: 160)]
    public ?string $addressLine2 = null;

    #[Assert\Regex(pattern: '/^\d{5}$/')]
    public ?string $postalCode = null;

    #[Assert\Length(max: 100)]
    public ?string $locality = null;

    #[Assert\Range(min: -90, max: 90)]
    public ?float $latitude = null;

    #[Assert\Range(min: -180, max: 180)]
    public ?float $longitude = null;

    #[Assert\Regex(pattern: '/^\+?[0-9 .-]{10,20}$/')]
    public ?string $phone = null;

    #[Assert\Email]
    public ?string $email = null;

    #[Assert\Url]
    public ?string $website = null;

    #[Assert\Length(max: 2000)]
    public ?string $description = null;

    #[Assert\Range(min: 0, max: 80)]
    public ?int $defaultDiscount = null;

    public ?int $managerMemberId = null;
}