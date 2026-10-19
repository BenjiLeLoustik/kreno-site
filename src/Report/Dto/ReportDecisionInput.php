<?php

namespace App\Report\Dto;

use App\Report\Enum\ReportDecision;
use NeoPHP\Component\Validator\Constraint as Assert;

class ReportDecisionInput
{
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [ReportDecision::class, 'values'])]
    public ?string $decision = null;

    #[Assert\NotBlank(message: 'Expliquez la décision : elle est envoyée aux deux parties.')]
    #[Assert\Length(min: 5, max: 1000)]
    public ?string $message = null;

    #[Assert\Range(min: 1, max: 365)]
    public int $days = 30;
}