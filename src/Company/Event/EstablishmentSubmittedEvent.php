<?php

namespace App\Company\Event;

use App\Entity\Company\CompanyEstablishment;

class EstablishmentSubmittedEvent
{
    public function __construct(
        public CompanyEstablishment $establishment,
    ) {
    }
}