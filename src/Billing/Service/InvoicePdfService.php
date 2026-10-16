<?php

namespace App\Billing\Service;

use App\Billing\Enum\InvoiceType;
use App\Billing\Presenter\InvoicePresenter;
use App\Entity\Company\CompanyInvoice;
use App\Shared\Storage\PrivateStorage;
use Dompdf\Dompdf;
use Dompdf\Options;
use NeoPHP\Component\View\ViewManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class InvoicePdfService
{
    public const TEMPLATE = 'pdf/invoice.html.twig';

    public function __construct(
        private ViewManagerInterface $views,
        private InvoicePresenter $presenter,
        private BillingInfoService $billingInfo,
        private PrivateStorage $storage,
        private OrmManagerInterface $orm,
    ) {
    }

    public function generate(CompanyInvoice $invoice): string
    {
        if ($this->storage->exists($invoice->getPdfPath())) {
            return (string) $invoice->getPdfPath();
        }

        $company = $invoice->getCompany();
        $html = $this->views->render(self::TEMPLATE, [
            'invoice' => $this->presenter->detail($invoice, $company !== null ? $this->billingInfo->billingEmail($company) : null),
            'creditNote' => $invoice->getType() === InvoiceType::CREDIT_NOTE,
        ]);

        $path = $this->storage->write(sprintf('invoices/%s/%s.pdf', ($invoice->getIssuedAt() ?? $invoice->getCreatedAt())?->format('Y') ?? date('Y'), $invoice->getNumber()), $this->render($html));
        $invoice->setPdfPath($path);
        $this->orm->flush();

        return $path;
    }

    public function content(CompanyInvoice $invoice): string
    {
        return $this->storage->read($this->generate($invoice));
    }

    public function fileName(CompanyInvoice $invoice): string
    {
        return sprintf('%s-%s.pdf', $invoice->getType() === InvoiceType::CREDIT_NOTE ? 'avoir' : 'facture', $invoice->getNumber());
    }

    private function render(string $html): string
    {
        $options = new Options();
        $options->setDefaultFont('DejaVu Sans');
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return (string) $dompdf->output();
    }
}