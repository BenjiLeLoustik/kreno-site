<?php

namespace App\Billing\Service;

use App\Billing\Enum\InvoiceStatus;
use App\Billing\Enum\InvoiceType;
use App\Billing\Event\InvoiceIssuedEvent;
use App\Billing\Helper\InvoiceNumberHelper;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyInvoice;
use App\Entity\Company\CompanyInvoiceLine;
use App\Entity\Company\CompanySubscription;
use App\Repository\Company\CompanyInvoiceRepository;
use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\MoneyHelper;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class InvoiceService
{
    public const DEFAULT_TAX_RATE = 20;

    public function __construct(
        private OrmManagerInterface $orm,
        private EventManagerInterface $events,
        private CompanyInvoiceRepository $invoices,
        private BillingInfoService $billingInfo,
    ) {
    }

    public function issue(Company $company, array $lines, DateTimeImmutable $periodStart, DateTimeImmutable $periodEnd, ?CompanySubscription $subscription = null, InvoiceType $type = InvoiceType::INVOICE, ?CompanyInvoice $credited = null): CompanyInvoice
    {
        $now = new DateTimeImmutable();
        $taxRate = $subscription?->getPlan()?->getVatRate() ?? $credited?->getTaxRate() ?? self::DEFAULT_TAX_RATE;

        $invoice = new CompanyInvoice();
        $invoice->setSubscription($subscription);
        $invoice->setCreditedInvoice($credited);
        $invoice->setType($type);
        $invoice->setNumber(InvoiceNumberHelper::next($type, (int) $now->format('Y'), $this->invoices->countNumbered($type, (int) $now->format('Y')), fn (string $number): bool => $this->invoices->numberExists($number)));
        $invoice->setPeriodStart($periodStart);
        $invoice->setPeriodEnd($periodEnd);
        $invoice->setIssuedAt($now);
        $invoice->setDueAt($now);
        $invoice->setTaxRate($taxRate);
        $invoice->setBillingName($this->billingInfo->billingName($company));
        $invoice->setBillingAddress($this->billingInfo->billingAddress($company));
        $invoice->setBillingVatNumber($company->getVatNumber());

        $total = 0;

        foreach (array_values($lines) as $position => $data) {
            $line = new CompanyInvoiceLine();
            $line->setLabel($data['label']);
            $line->setQuantity($data['quantity'] ?? 1);
            $line->setUnitPrice($data['unitPrice']);
            $line->setTotal($data['total'] ?? ($data['quantity'] ?? 1) * $data['unitPrice']);
            $line->setEstablishment($data['establishment'] ?? null);
            $line->setPosition($position);

            foreach ($data['bookings'] ?? [] as $booking) {
                $line->addBooking($booking);
            }

            $invoice->addLine($line);
            $this->orm->persist($line);
            $total += $line->getTotal();
        }

        $tax = MoneyHelper::tax($total, $taxRate);
        $invoice->setTotalExcludingTax($total);
        $invoice->setTaxAmount($tax);
        $invoice->setTotalIncludingTax($total + $tax);
        $invoice->setStatus($type === InvoiceType::CREDIT_NOTE || $total + $tax <= 0 ? InvoiceStatus::PAID : InvoiceStatus::OPEN);
        $invoice->setPaidAt($invoice->getStatus() === InvoiceStatus::PAID ? $now : null);

        $company->addInvoice($invoice);
        $this->orm->persist($invoice);
        $this->orm->flush();

        $this->events->dispatch(new InvoiceIssuedEvent($invoice));

        return $invoice;
    }

    public function issueForSubscription(CompanySubscription $subscription, int $amount, string $label, DateTimeImmutable $periodStart, DateTimeImmutable $periodEnd, array $extraLines = []): CompanyInvoice
    {
        $company = $subscription->getCompany() ?? throw new BusinessRuleException('Abonnement sans entreprise.');

        return $this->issue($company, [['label' => $label, 'unitPrice' => $amount], ...$extraLines], $periodStart, $periodEnd, $subscription);
    }

    public function credit(CompanyInvoice $invoice, ?int $amount, string $label): CompanyInvoice
    {
        $company = $invoice->getCompany() ?? throw new BusinessRuleException('Facture sans entreprise.');

        if ($invoice->getType() !== InvoiceType::INVOICE || $invoice->getTotalExcludingTax() <= 0) {
            throw new BusinessRuleException('Cette facture ne peut pas faire l\'objet d\'un avoir.');
        }

        $amount = min($amount ?? $invoice->getTotalExcludingTax(), $invoice->getTotalExcludingTax());

        return $this->issue($company, [['label' => $label, 'unitPrice' => -$amount]], $invoice->getPeriodStart() ?? new DateTimeImmutable(), $invoice->getPeriodEnd() ?? new DateTimeImmutable(), $invoice->getSubscription(), InvoiceType::CREDIT_NOTE, $invoice);
    }

    public function void(CompanyInvoice $invoice): void
    {
        if ($invoice->getStatus() === InvoiceStatus::PAID) {
            throw new BusinessRuleException('Une facture payée ne peut pas être annulée : émettez un avoir.');
        }

        $invoice->setStatus(InvoiceStatus::VOID);
        $this->orm->flush();
    }
}