<?php

namespace App\Support\Dto;

use App\Support\Enum\ContactTopic;
use NeoPHP\Component\Validator\Constraint as Assert;

class ContactInput
{
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [ContactTopic::class, 'values'])]
    public ?string $topic = null;

    #[Assert\Regex(pattern: '/^KR-\d{6}$/')]
    public ?string $reference = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 3000)]
    public ?string $message = null;
}