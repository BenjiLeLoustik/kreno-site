<?php

namespace App\Faq\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class FaqQuestionInput
{
    #[Assert\NotBlank(message: 'Indiquez la question.')]
    #[Assert\Length(min: 5, max: 255)]
    public ?string $question = null;

    #[Assert\NotBlank(message: 'Indiquez la réponse.')]
    #[Assert\Length(max: 5000)]
    public ?string $answer = null;
}