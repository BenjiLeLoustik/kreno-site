<?php

namespace App\Billing\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class BillingInfoInput
{
    #[Assert\NotBlank(message: 'Indiquez la raison sociale.')]
    #[Assert\Length(max: 160)]
    public ?string $billingName = null;

    #[Assert\NotBlank(message: 'Indiquez l\'e-mail qui recevra les factures.')]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public ?string $billingEmail = null;

    #[Assert\Regex(pattern: '/^[A-Z]{2}[0-9A-Z]{2,13}$/', message: 'Numéro de TVA intracommunautaire invalide.')]
    public ?string $vatNumber = null;

    #[Assert\NotBlank(message: 'Indiquez l\'adresse de facturation.')]
    #[Assert\Length(max: 160)]
    public ?string $addressLine1 = null;

    #[Assert\Length(max: 160)]
    public ?string $addressLine2 = null;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d{5}$/')]
    public ?string $postalCode = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public ?string $locality = null;
}