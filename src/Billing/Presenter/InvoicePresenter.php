<?php

namespace App\Billing\Presenter;

use App\Billing\Enum\InvoiceDisplayStatus;
use App\Billing\Enum\InvoiceStatus;
use App\Billing\Enum\InvoiceType;
use App\Billing\Enum\PaymentStatus;
use App\Billing\Helper\PaymentMethodHelper;
use App\Billing\Helper\PeriodHelper;
use App\Entity\Company\CompanyInvoice;
use App\Entity\Company\CompanyInvoiceLine;
use App\Entity\Company\CompanyPayment;
use App\Shared\Helper\DateHelper;
use App\Shared\Helper\MoneyHelper;
use App\Shared\Helper\Translation;
use NeoPHP\Component\Container\Attribute\Autowire;

class InvoicePresenter
{
    public function __construct(
        #[Autowire(config: 'packages.billing.issuer')]
        private array $issuer = [],
    ) {
    }

    public function status(CompanyInvoice $invoice): InvoiceDisplayStatus
    {
        if ($this->isCreditNote($invoice)) {
            return InvoiceDisplayStatus::REFUNDED;
        }

        return match ($invoice->getStatus()) {
            InvoiceStatus::PAID => InvoiceDisplayStatus::PAID,
            InvoiceStatus::FAILED => InvoiceDisplayStatus::FAILED,
            InvoiceStatus::VOID => InvoiceDisplayStatus::VOID,
            default => InvoiceDisplayStatus::OPEN,
        };
    }

    public function row(CompanyInvoice $invoice): array
    {
        $status = $this->status($invoice);
        $total = $invoice->getTotalIncludingTax();

        return [
            'id' => $invoice->getId(),
            'number' => $invoice->getNumber(),
            'type' => $invoice->getType()->value,
            'date' => PeriodHelper::day($invoice->getIssuedAt()),
            'issuedAt' => DateHelper::iso($invoice->getIssuedAt()),
            'detail' => $this->detailLabel($invoice),
            'amount' => MoneyHelper::formatExact($total),
            'amountCents' => $total,
            'kind' => $status->value,
            'status' => $status->label(),
            'canPay' => $status->isPayable() && $total > 0,
        ];
    }

    public function rows(array $invoices): array
    {
        return array_values(array_map(fn (CompanyInvoice $invoice): array => $this->row($invoice), $invoices));
    }

    public function detail(CompanyInvoice $invoice, ?string $billingEmail = null): array
    {
        return $this->row($invoice) + [
                'subtitle' => $this->summary($invoice),
                'period' => $this->period($invoice),
                'due' => $this->due($invoice),
                'lines' => $this->lines($invoice),
                'ht' => MoneyHelper::formatExact($invoice->getTotalExcludingTax()),
                'vatLabel' => sprintf('TVA %d %%', $invoice->getTaxRate()),
                'vat' => MoneyHelper::formatExact($invoice->getTaxAmount()),
                'ttc' => MoneyHelper::formatExact($invoice->getTotalIncludingTax()),
                'stamp' => $this->status($invoice)->stamp(),
                'payment' => $this->paymentSentence($invoice),
                'billedTo' => $this->billedTo($invoice, $billingEmail),
                'issuer' => $this->issuer,
                'creditedInvoice' => $invoice->getCreditedInvoice()?->getNumber(),
                'legalNotice' => self::lateFeeNotice(),
            ];
    }

    public static function lateFeeNotice(): string
    {
        return Translation::translate(
            'En cas de retard de paiement, indemnité forfaitaire pour frais de recouvrement de 40 €  (art. L441-10 du Code de commerce). Pas d\'escompte pour paiement anticipé.'
        );
    }

    private function isCreditNote(CompanyInvoice $invoice): bool
    {
        return $invoice->getType() === InvoiceType::CREDIT_NOTE;
    }

    private function detailLabel(CompanyInvoice $invoice): string
    {
        $lines = $invoice->getLines()->toArray();

        if (count($lines) === 1 && $lines[0] instanceof CompanyInvoiceLine) {
            return $lines[0]->getLabel();
        }

        return $this->summary($invoice);
    }

    private function period(CompanyInvoice $invoice): ?string
    {
        $start = $invoice->getPeriodStart();
        $end = $invoice->getPeriodEnd();

        if ($start === null || $end === null) {
            return null;
        }

        return PeriodHelper::label($start, $end);
    }

    private function due(CompanyInvoice $invoice): ?string
    {
        if ($this->isCreditNote($invoice)) {
            return '—';
        }

        return PeriodHelper::day($invoice->getDueAt());
    }

    private function lines(CompanyInvoice $invoice): array
    {
        $lines = [];

        foreach ($invoice->getLines() as $line) {
            $lines[] = [
                'label' => $line->getLabel(),
                'qty' => (string) $line->getQuantity(),
                'unit' => MoneyHelper::formatExact($line->getUnitPrice()),
                'total' => MoneyHelper::formatExact($line->getTotal()),
            ];
        }

        return $lines;
    }

    private function billedTo(CompanyInvoice $invoice, ?string $billingEmail): array
    {
        return [
            'name' => $invoice->getBillingName(),
            'address' => $invoice->getBillingAddress(),
            'vatNumber' => $invoice->getBillingVatNumber(),
            'email' => $billingEmail,
        ];
    }

    private function summary(CompanyInvoice $invoice): string
    {
        if ($this->isCreditNote($invoice)) {
            return $this->creditNoteSummary($invoice);
        }

        $month = $this->month($invoice);
        $subscription = $invoice->getSubscription();

        if ($subscription !== null) {
            return sprintf('%s · %s', $subscription->getPlan()?->getName(), $month);
        }

        $count = $this->bookedSlots($invoice);

        if ($count > 1) {
            return sprintf(
                Translation::translate('À la réservation, %d créneaux réservés · %s'),
                $count,
                $month);
        }

        return sprintf(
            Translation::translate('À la réservation, %d créneau réservé · %s'),
            $count,
            $month
        );
    }

    private function creditNoteSummary(CompanyInvoice $invoice): string
    {
        $credited = $invoice->getCreditedInvoice();

        if ($credited === null) {
            return Translation::translate('Avoir');
        }

        return sprintf(
            Translation::translate('Avoir sur la facture %s'),
            $credited->getNumber()
        );
    }

    private function month(CompanyInvoice $invoice): string
    {
        $start = $invoice->getPeriodStart();

        if ($start === null) {
            return '';
        }

        return PeriodHelper::monthName($start) . ' ' . $start->format('Y');
    }

    private function bookedSlots(CompanyInvoice $invoice): int
    {
        $count = 0;

        foreach ($invoice->getLines() as $line) {
            $count += $line->getQuantity();
        }

        return $count;
    }

    private function paymentSentence(CompanyInvoice $invoice): string
    {
        if ($this->isCreditNote($invoice)) {
            return $this->creditNoteSentence($invoice);
        }

        $failures = $this->failedPayments($invoice);

        if ($invoice->getStatus() !== InvoiceStatus::PAID) {
            return $this->unpaidSentence($failures);
        }

        $succeeded = $this->succeededPayment($invoice);

        if ($succeeded === null) {
            return Translation::translate('Rien à régler.');
        }

        return $this->paidSentence($succeeded, $failures);
    }

    private function creditNoteSentence(CompanyInvoice $invoice): string
    {
        $credited = $invoice->getCreditedInvoice();

        if ($credited === null) {
            return Translation::translate('Avoir déduit de la prochaine facture.');
        }

        return sprintf(
            Translation::translate('Avoir rattaché à la facture %s.'),
            $credited->getNumber()
        );
    }

    private function unpaidSentence(int $failures): string
    {
        if ($failures > 0) {
            return sprintf(
                Translation::translate('Paiement refusé %d fois. À régler depuis votre espace.'),
                $failures
            );
        }

        return Translation::translate('À régler à réception.');
    }

    private function paidSentence(CompanyPayment $payment, int $failures): string
    {
        $date = PeriodHelper::day($payment->getSucceededAt());
        $method = PaymentMethodHelper::label($payment->getPaymentMethod()) ?? 'paiement en ligne';

        if ($failures > 0) {
            return sprintf(
                Translation::translate('Payée le %s par %s, après %d refus.'),
                $date,
                $method,
                $failures
            );
        }

        return sprintf(
            Translation::translate('Payée le %s par %s.'),
            $date,
            $method
        );
    }

    private function succeededPayment(CompanyInvoice $invoice): ?CompanyPayment
    {
        $succeeded = null;

        foreach ($invoice->getPayments() as $payment) {
            if ($payment->getStatus() === PaymentStatus::SUCCEEDED) {
                $succeeded = $payment;
            }
        }

        return $succeeded;
    }

    private function failedPayments(CompanyInvoice $invoice): int
    {
        $failures = 0;

        foreach ($invoice->getPayments() as $payment) {
            if ($payment->getStatus() === PaymentStatus::FAILED) {
                ++$failures;
            }
        }

        return $failures;
    }
}