<?php

namespace App\Catalog\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class PositionsInput
{
    #[Assert\NotBlank]
    #[Assert\All([new Assert\Regex(pattern: '/^[a-z0-9-]+$/')])]
    public array $slugs = [];
}