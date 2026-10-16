<?php

namespace App\Controller\Pro;

use App\Billing\Helper\InvoiceNumberHelper;
use App\Billing\Service\InvoicePdfService;
use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Repository\Company\CompanyInvoiceRepository;
use App\Shared\Helper\DownloadHelper;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class InvoicePdfController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        private CompanyInvoiceRepository $invoices,
        private InvoicePdfService $pdf,
    ) {
        parent::__construct($context, $resolver);
    }

    #[Route('/pro/billing/invoices/{number}/pdf', name: 'pro_billing_invoice_pdf', methods: ['GET'], requirements: ['number' => InvoiceNumberHelper::PATTERN])]
    public function download(string $number): Response
    {
        $company = $this->currentCompany();
        $this->requirePermission(Permission::COMPANY_BILLING, $company);
        $invoice = $this->invoices->findOneForCompany($company, $number) ?? throw $this->createNotFoundException('Facture introuvable.');

        return DownloadHelper::response($this->pdf->content($invoice), $this->pdf->fileName($invoice));
    }
}