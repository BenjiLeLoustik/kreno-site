<?php

namespace App\Report\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class EstablishmentReportInput
{
    #[Assert\NotBlank(message: 'Choisissez la réservation concernée.')]
    #[Assert\Positive]
    public ?int $bookingId = null;

    #[Assert\NotBlank(message: 'Choisissez un motif.')]
    #[Assert\Choice(choices: ['no_show', 'late', 'behavior', 'other'])]
    public ?string $reason = null;

    #[Assert\NotBlank(message: 'Décrivez ce qui s\'est passé.')]
    #[Assert\Length(min: 10, max: 2000)]
    public ?string $description = null;
}