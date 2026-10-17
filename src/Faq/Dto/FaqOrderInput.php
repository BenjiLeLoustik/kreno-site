<?php

namespace App\Faq\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class FaqOrderInput
{
    #[Assert\NotBlank]
    #[Assert\All([new Assert\Regex(pattern: '/^[1-9][0-9]*$/')])]
    public array $ids = [];
}