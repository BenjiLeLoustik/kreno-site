<?php

namespace App\Establishment\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class OrderInput
{
    #[Assert\NotBlank]
    #[Assert\All([new Assert\Positive()])]
    public array $ids = [];
}