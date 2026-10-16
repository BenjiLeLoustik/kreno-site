<?php

namespace App\Company\Event;

use App\Entity\Company\CompanyEstablishment;

class DocumentRequestedEvent
{
    public function __construct(
        public CompanyEstablishment $establishment,
        public string $message,
    ) {
    }
}