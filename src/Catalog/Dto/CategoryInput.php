<?php

namespace App\Catalog\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class CategoryInput
{
    #[Assert\Length(min: 2, max: 80)]
    public ?string $name = null;

    #[Assert\Regex(pattern: '/^[a-z0-9-]{2,50}$/')]
    public ?string $icon = null;

    public ?string $description = null;

    public ?bool $visible = null;

    #[Assert\PositiveOrZero]
    public ?int $position = null;
}