<?php

namespace App\Support\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class ContactReplyInput
{
    #[Assert\NotBlank(message: 'Écrivez votre message.')]
    #[Assert\Length(min: 2, max: 3000)]
    public ?string $body = null;
}