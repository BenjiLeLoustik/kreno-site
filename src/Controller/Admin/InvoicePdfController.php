<?php

namespace App\Controller\Admin;

use App\Billing\Helper\InvoiceNumberHelper;
use App\Billing\Service\InvoicePdfService;
use App\Repository\Company\CompanyInvoiceRepository;
use App\Shared\Helper\DownloadHelper;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class InvoicePdfController extends AbstractAdminController
{
    public function __construct(
        private CompanyInvoiceRepository $invoices,
        private InvoicePdfService $pdf,
    ) {
    }

    #[Route('/admin/billing/invoices/{number}/pdf', name: 'admin_billing_invoice_pdf', methods: ['GET'], requirements: ['number' => InvoiceNumberHelper::PATTERN])]
    public function download(string $number): Response
    {
        $this->currentAdmin();
        $invoice = $this->invoices->findOneByNumber($number) ?? throw $this->createNotFoundException('Facture introuvable.');

        return DownloadHelper::response($this->pdf->content($invoice), $this->pdf->fileName($invoice));
    }
}