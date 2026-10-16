<?php

namespace App\Establishment\Event;

use App\Entity\Company\CompanyEstablishment;

class EstablishmentSuspendedEvent
{
    public function __construct(
        private CompanyEstablishment $establishment,
        private string $reason,
    ) {
    }

    public function getEstablishment(): CompanyEstablishment
    {
        return $this->establishment;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}