<?php

namespace App\Report\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class ReportMessageInput
{
    #[Assert\NotBlank(message: 'Écrivez votre message.')]
    #[Assert\Length(min: 2, max: 2000)]
    public ?string $body = null;
}