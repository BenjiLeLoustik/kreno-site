<?php

namespace App\Company\Event;

use App\Entity\Company\CompanyEstablishment;

class EstablishmentRejectedEvent
{
    public function __construct(
        public CompanyEstablishment $establishment,
        public string $reason,
        public ?string $message = null,
    ) {
    }
}