<?php

namespace App\Billing\Provider;

use App\Billing\Exception\InvoiceNotFoundException;
use App\Entity\Company\CompanyInvoice;
use App\Repository\Company\CompanyInvoiceRepository;

class AdminInvoiceProvider
{
    public function __construct(
        private CompanyInvoiceRepository $invoices,
    ) {
    }

    public function invoice(string $number): CompanyInvoice
    {
        $invoice = $this->invoices->findOneByNumber($number);

        if ($invoice === null) {
            throw new InvoiceNotFoundException();
        }

        return $invoice;
    }
}