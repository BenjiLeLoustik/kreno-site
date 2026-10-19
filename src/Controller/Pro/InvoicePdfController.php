<?php

namespace App\Controller\Pro;

use App\Billing\Helper\InvoiceNumberHelper;
use App\Billing\Provider\BillingPageProvider;
use App\Billing\Service\InvoicePdfService;
use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Provider\ProContextProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Shared\Helper\DownloadHelper;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class InvoicePdfController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        ProContextProvider $pro,
        private BillingPageProvider $pages,
        private InvoicePdfService $pdf,
    ) {
        parent::__construct($context, $resolver, $pro);
    }

    #[Route(
        '/pro/billing/invoices/{number}/pdf',
        name: 'pro_billing_invoice_pdf',
        methods: ['GET'],
        requirements: ['number' => InvoiceNumberHelper::PATTERN],
    )]
    public function download(string $number): Response
    {
        $company = $this->permittedCompany(Permission::COMPANY_BILLING);
        $invoice = $this->pages->invoice($company, $number);

        return DownloadHelper::response($this->pdf->content($invoice), $this->pdf->fileName($invoice));
    }
}