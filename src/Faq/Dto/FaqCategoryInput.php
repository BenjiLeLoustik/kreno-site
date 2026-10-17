<?php

namespace App\Faq\Dto;

use App\Faq\Enum\FaqAudience;
use NeoPHP\Component\Validator\Constraint as Assert;

class FaqCategoryInput
{
    #[Assert\NotBlank(message: 'Indiquez le nom de la rubrique.')]
    #[Assert\Length(min: 2, max: 80)]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'Choisissez le public de la rubrique.')]
    #[Assert\Choice(callback: [FaqAudience::class, 'values'])]
    public ?string $audience = null;
}