<?php

namespace App\Billing\Listener;

use App\Billing\Event\InvoiceIssuedEvent;
use App\Billing\Service\InvoicePdfService;
use NeoPHP\Component\Event\Attribute\AsListener;

class InvoicePdfListener
{
    public function __construct(
        private InvoicePdfService $pdf,
    ) {
    }

    #[AsListener(InvoiceIssuedEvent::class)]
    public function onIssued(InvoiceIssuedEvent $event): void
    {
        $this->pdf->generate($event->getInvoice());
    }
}