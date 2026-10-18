<?php

namespace App\Billing\Service;

use App\Billing\Enum\InvoiceStatus;
use App\Billing\Enum\InvoiceType;
use App\Billing\Event\InvoiceIssuedEvent;
use App\Billing\Exception\InvoiceNotCreditableException;
use App\Billing\Exception\InvoiceWithoutCompanyException;
use App\Billing\Exception\PaidInvoiceVoidException;
use App\Billing\Exception\SubscriptionWithoutCompanyException;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyInvoice;
use App\Entity\Company\CompanyInvoiceLine;
use App\Entity\Company\CompanySubscription;
use App\Repository\Company\CompanyInvoiceRepository;
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
        private InvoiceNumberGenerator $numberGenerator,
    ) {
    }

    public function issue(
        Company $company,
        array $lines,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
        ?CompanySubscription $subscription = null,
        InvoiceType $type = InvoiceType::INVOICE,
        ?CompanyInvoice $credited = null,
    ): CompanyInvoice {
        $now = new DateTimeImmutable();
        $taxRate = $subscription?->getPlan()?->getVatRate() ?? $credited?->getTaxRate() ?? self::DEFAULT_TAX_RATE;

        $invoice = new CompanyInvoice();
        $invoice->setSubscription($subscription);
        $invoice->setCreditedInvoice($credited);
        $invoice->setType($type);
        $invoice->setNumber($this->nextNumber($type, $now));
        $invoice->setPeriodStart($periodStart);
        $invoice->setPeriodEnd($periodEnd);
        $invoice->setIssuedAt($now);
        $invoice->setDueAt($now);
        $invoice->setTaxRate($taxRate);
        $invoice->setBillingName($this->billingInfo->billingName($company));
        $invoice->setBillingAddress($this->billingInfo->billingAddress($company));
        $invoice->setBillingVatNumber($company->getVatNumber());

        $total = $this->addLines($invoice, $lines);
        $tax = MoneyHelper::tax($total, $taxRate);
        $invoice->setTotalExcludingTax($total);
        $invoice->setTaxAmount($tax);
        $invoice->setTotalIncludingTax($total + $tax);
        $this->settleIfNothingDue($invoice, $now);

        $company->addInvoice($invoice);
        $this->orm->persist($invoice);
        $this->orm->flush();

        $this->events->dispatch(new InvoiceIssuedEvent($invoice));

        return $invoice;
    }

    public function issueForSubscription(
        CompanySubscription $subscription,
        int $amount,
        string $label,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
        array $extraLines = [],
    ): CompanyInvoice {
        $company = $subscription->getCompany() ?? throw new SubscriptionWithoutCompanyException();
        $lines = [
            [
                'label' => $label,
                'unitPrice' => $amount
            ],
            ...$extraLines
        ];

        return $this->issue($company, $lines, $periodStart, $periodEnd, $subscription);
    }

    public function credit(CompanyInvoice $invoice, ?int $amount, string $label): CompanyInvoice
    {
        $company = $invoice->getCompany() ?? throw new InvoiceWithoutCompanyException();
        $total = $invoice->getTotalExcludingTax();

        if ($invoice->getType() !== InvoiceType::INVOICE || $total <= 0) {
            throw new InvoiceNotCreditableException();
        }

        $amount = min($amount ?? $total, $total);

        return $this->issue(
            $company,
            [['label' => $label, 'unitPrice' => -$amount]],
            $invoice->getPeriodStart() ?? new DateTimeImmutable(),
            $invoice->getPeriodEnd() ?? new DateTimeImmutable(),
            $invoice->getSubscription(),
            InvoiceType::CREDIT_NOTE,
            $invoice,
        );
    }

    public function void(CompanyInvoice $invoice): void
    {
        if ($invoice->getStatus() === InvoiceStatus::PAID) {
            throw new PaidInvoiceVoidException();
        }

        $invoice->setStatus(InvoiceStatus::VOID);
        $this->orm->flush();
    }

    private function nextNumber(InvoiceType $type, DateTimeImmutable $now): string
    {
        $year = (int) $now->format('Y');

        return $this->numberGenerator->next(
            $type,
            $year,
            $this->invoices->countNumbered($type, $year),
            fn (string $number): bool => $this->invoices->numberExists($number),
        );
    }

    private function addLines(CompanyInvoice $invoice, array $lines): int
    {
        $total = 0;

        foreach (array_values($lines) as $position => $data) {
            $line = $this->line($data, $position);
            $invoice->addLine($line);
            $this->orm->persist($line);
            $total += $line->getTotal();
        }

        return $total;
    }

    private function line(array $data, int $position): CompanyInvoiceLine
    {
        $quantity = $data['quantity'] ?? 1;

        $line = new CompanyInvoiceLine();
        $line->setLabel($data['label']);
        $line->setQuantity($quantity);
        $line->setUnitPrice($data['unitPrice']);
        $line->setTotal($data['total'] ?? $quantity * $data['unitPrice']);
        $line->setEstablishment($data['establishment'] ?? null);
        $line->setPosition($position);

        foreach ($data['bookings'] ?? [] as $booking) {
            $line->addBooking($booking);
        }

        return $line;
    }

    private function settleIfNothingDue(CompanyInvoice $invoice, DateTimeImmutable $now): void
    {
        if ($invoice->getType() === InvoiceType::CREDIT_NOTE || $invoice->getTotalIncludingTax() <= 0) {
            $invoice->setStatus(InvoiceStatus::PAID);
            $invoice->setPaidAt($now);

            return;
        }

        $invoice->setStatus(InvoiceStatus::OPEN);
        $invoice->setPaidAt(null);
    }
}