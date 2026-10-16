<?php

namespace App\Website\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class WebsiteScheduleInput
{
    #[Assert\NotBlank]
    #[Assert\DateTime(format: 'Y-m-d\TH:i')]
    public string $scheduledAt = '';
}