<?php

namespace App\Establishment\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class ServiceInput
{
    #[Assert\NotBlank(message: 'Donnez un nom à la prestation.')]
    #[Assert\Length(min: 2, max: 120)]
    public ?string $name = null;

    #[Assert\Length(max: 255)]
    public ?string $description = null;

    #[Assert\NotBlank(message: 'Indiquez le prix habituel.')]
    #[Assert\Range(min: 100, max: 500000)]
    public ?int $price = null;

    #[Assert\NotBlank(message: 'Indiquez la durée.')]
    #[Assert\Range(min: 5, max: 600)]
    public ?int $duration = null;

    #[Assert\Range(min: 0, max: 90)]
    public ?int $defaultDiscount = null;

    public ?bool $active = null;
}