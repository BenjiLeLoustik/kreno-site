<?php

namespace App\Report\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class ReportDecisionInput
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['dismiss', 'warn', 'suspend', 'resolve'])]
    public ?string $decision = null;

    #[Assert\NotBlank(message: 'Expliquez la décision : elle est envoyée aux deux parties.')]
    #[Assert\Length(min: 5, max: 1000)]
    public ?string $message = null;

    #[Assert\Range(min: 1, max: 365)]
    public int $days = 30;
}