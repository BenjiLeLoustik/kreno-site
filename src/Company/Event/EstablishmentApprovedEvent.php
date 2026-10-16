<?php

namespace App\Company\Event;

use App\Entity\Company\CompanyEstablishment;

class EstablishmentApprovedEvent
{
    public function __construct(
        public CompanyEstablishment $establishment,
    ) {
    }
}