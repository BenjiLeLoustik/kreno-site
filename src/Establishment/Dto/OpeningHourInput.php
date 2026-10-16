<?php

namespace App\Establishment\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class OpeningHourInput
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: [1, 2, 3, 4, 5, 6, 7])]
    public ?int $weekday = null;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^([01]\d|2[0-3]):[0-5]\d$/', message: 'Heure au format HH:MM.')]
    public ?string $opensAt = null;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^([01]\d|2[0-3]):[0-5]\d$/', message: 'Heure au format HH:MM.')]
    public ?string $closesAt = null;
}