<?php

namespace App\Booking\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class BookingInput
{
    #[Assert\Length(max: 80)]
    public ?string $firstName = null;

    #[Assert\Regex(pattern: '/^\+?[0-9 .-]{10,20}$/', message: 'Numéro de téléphone invalide.')]
    public ?string $phone = null;

    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    #[Assert\Length(max: 500)]
    public ?string $note = null;
}