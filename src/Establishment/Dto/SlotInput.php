<?php

namespace App\Establishment\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class SlotInput
{
    #[Assert\NotBlank(message: 'Choisissez une prestation.')]
    #[Assert\Positive]
    public ?int $serviceId = null;

    #[Assert\NotBlank(message: 'Choisissez un jour.')]
    #[Assert\Date]
    public ?string $day = null;

    #[Assert\NotBlank(message: 'Choisissez une heure.')]
    #[Assert\Regex(pattern: '/^([01]\d|2[0-3]):[0-5]\d$/', message: 'Heure au format HH:MM.')]
    public ?string $time = null;

    #[Assert\Range(min: 0, max: 90)]
    public ?int $discount = null;

    #[Assert\Range(min: 0, max: 1440)]
    public ?int $removalMinutes = null;

    #[Assert\Positive]
    public ?int $staffId = null;
}