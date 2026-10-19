<?php

namespace App\Review\Dto;

use App\Review\Enum\ReviewRating;
use NeoPHP\Component\Validator\Constraint as Assert;

class ReviewInput
{
    #[Assert\NotBlank(message: 'Choisis une note.')]
    #[Assert\Choice(callback: [ReviewRating::class, 'values'], message: 'Choisis une note entre 1 et 5.')]
    public ?string $rating = null;

    #[Assert\Length(max: 1000)]
    public ?string $comment = null;
}