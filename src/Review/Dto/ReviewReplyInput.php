<?php

namespace App\Review\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class ReviewReplyInput
{
    #[Assert\NotBlank(message: 'Écrivez votre réponse.')]
    #[Assert\Length(min: 2, max: 1000)]
    public ?string $body = null;
}