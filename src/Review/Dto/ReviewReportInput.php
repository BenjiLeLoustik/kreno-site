<?php

namespace App\Review\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class ReviewReportInput
{
    #[Assert\NotBlank(message: 'Indiquez pourquoi cet avis pose problème.')]
    #[Assert\Length(min: 10, max: 500)]
    public ?string $reason = null;
}