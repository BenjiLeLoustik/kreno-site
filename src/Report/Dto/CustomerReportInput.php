<?php

namespace App\Report\Dto;

use App\Report\Enum\ReportReason;
use NeoPHP\Component\Validator\Constraint as Assert;

class CustomerReportInput
{
    #[Assert\Regex(pattern: '/^KR-\d{6}$/')]
    public ?string $booking = null;

    #[Assert\Positive]
    public ?int $establishmentId = null;

    #[Assert\NotBlank(message: 'Choisis un motif.')]
    #[Assert\Choice(callback: [ReportReason::class, 'customerValues'])]
    public ?string $reason = null;

    #[Assert\NotBlank(message: 'Raconte-nous ce qui s\'est passé.')]
    #[Assert\Length(min: 10, max: 2000)]
    public ?string $description = null;
}