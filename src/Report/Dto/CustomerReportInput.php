<?php

namespace App\Report\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class CustomerReportInput
{
    #[Assert\Regex(pattern: '/^KR-\d{6}$/')]
    public ?string $booking = null;

    #[Assert\Positive]
    public ?int $establishmentId = null;

    #[Assert\NotBlank(message: 'Choisissez un motif.')]
    #[Assert\Choice(choices: ['price_mismatch', 'closed', 'service_mismatch', 'last_minute_cancellation', 'behavior', 'other'])]
    public ?string $reason = null;

    #[Assert\NotBlank(message: 'Raconte-nous ce qui s\'est passé.')]
    #[Assert\Length(min: 10, max: 2000)]
    public ?string $description = null;
}