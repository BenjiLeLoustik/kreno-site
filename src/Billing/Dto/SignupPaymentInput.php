<?php

namespace App\Billing\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class SignupPaymentInput
{
    #[Assert\NotBlank]
    #[Assert\Valid]
    public ?BillingInfoInput $billing = null;

    #[Assert\NotBlank(message: 'Enregistrez un moyen de paiement.')]
    #[Assert\Length(max: 255)]
    public ?string $setupReference = null;

    public bool $mandateAccepted = false;

    #[Assert\IsTrue(message: 'Vous devez accepter les conditions générales de vente.')]
    public bool $acceptTerms = false;

    #[Assert\Length(max: 40)]
    public ?string $promoCode = null;
}