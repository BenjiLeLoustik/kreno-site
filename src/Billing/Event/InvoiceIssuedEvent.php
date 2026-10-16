<?php

namespace App\Billing\Event;

use App\Entity\Company\CompanyInvoice;

class InvoiceIssuedEvent
{
    public function __construct(
        private CompanyInvoice $invoice,
    ) {
    }

    public function getInvoice(): CompanyInvoice
    {
        return $this->invoice;
    }
}