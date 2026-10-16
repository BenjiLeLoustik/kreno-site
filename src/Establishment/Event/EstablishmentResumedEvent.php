<?php

namespace App\Establishment\Event;

use App\Entity\Company\CompanyEstablishment;

class EstablishmentResumedEvent
{
    public function __construct(
        private CompanyEstablishment $establishment,
    ) {
    }

    public function getEstablishment(): CompanyEstablishment
    {
        return $this->establishment;
    }
}