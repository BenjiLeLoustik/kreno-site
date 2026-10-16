<?php

namespace App\Report\Dto;

use App\Report\Enum\ReportReason;
use NeoPHP\Component\Validator\Constraint as Assert;

class EstablishmentReportInput
{
    #[Assert\NotBlank(message: 'Choisissez la réservation concernée.')]
    #[Assert\Positive]
    public ?int $bookingId = null;

    #[Assert\NotBlank(message: 'Choisissez un motif.')]
    #[Assert\Choice(callback: [ReportReason::class, 'establishmentValues'])]
    public ?string $reason = null;

    #[Assert\NotBlank(message: 'Décrivez ce qui s\'est passé.')]
    #[Assert\Length(min: 10, max: 2000)]
    public ?string $description = null;
}