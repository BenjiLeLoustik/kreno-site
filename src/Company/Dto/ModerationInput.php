<?php

namespace App\Company\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class ModerationInput
{
    #[Assert\Length(max: 120)]
    public ?string $reason = null;

    #[Assert\Length(max: 2000)]
    public ?string $message = null;
}