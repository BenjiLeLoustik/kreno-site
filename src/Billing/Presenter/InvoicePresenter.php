<?php

namespace App\Billing\Presenter;

use App\Billing\Enum\InvoiceStatus;
use App\Billing\Enum\InvoiceType;
use App\Billing\Enum\PaymentStatus;
use App\Billing\Helper\PaymentMethodHelper;
use App\Billing\Helper\PeriodHelper;
use App\Entity\Company\CompanyInvoice;
use App\Entity\Company\CompanyInvoiceLine;
use App\Shared\Helper\DateHelper;
use App\Shared\Helper\MoneyHelper;
use NeoPHP\Component\Container\Attribute\Autowire;

class InvoicePresenter
{
    public const LATE_FEE_NOTICE = 'En cas de retard de paiement, indemnité forfaitaire pour frais de recouvrement de 40 € (art. L441-10 du Code de commerce). Pas d\'escompte pour paiement anticipé.';

    public function __construct(
        #[Autowire(config: 'packages.billing.issuer')]
        private array $issuer = [],
    ) {
    }

    public function kind(CompanyInvoice $invoice): string
    {
        if ($invoice->getType() === InvoiceType::CREDIT_NOTE) {
            return 'refunded';
        }

        return match ($invoice->getStatus()) {
            InvoiceStatus::PAID => 'paid',
            InvoiceStatus::FAILED => 'failed',
            InvoiceStatus::VOID => 'void',
            default => 'open',
        };
    }

    public function row(CompanyInvoice $invoice): array
    {
        $lines = $invoice->getLines()->toArray();
        $kind = $this->kind($invoice);

        return [
            'id' => $invoice->getId(),
            'number' => $invoice->getNumber(),
            'type' => $invoice->getType()->value,
            'date' => PeriodHelper::day($invoice->getIssuedAt()),
            'issuedAt' => DateHelper::iso($invoice->getIssuedAt()),
            'detail' => count($lines) === 1 && $lines[0] instanceof CompanyInvoiceLine ? $lines[0]->getLabel() : $this->summary($invoice),
            'amount' => MoneyHelper::formatExact($invoice->getTotalIncludingTax()),
            'amountCents' => $invoice->getTotalIncludingTax(),
            'kind' => $kind,
            'status' => match ($kind) {
                'paid' => 'Payée',
                'failed' => 'Échec du paiement',
                'refunded' => 'Avoir',
                'void' => 'Annulée',
                default => 'À payer',
            },
            'canPay' => in_array($kind, ['open', 'failed'], true) && $invoice->getTotalIncludingTax() > 0,
        ];
    }

    public function rows(array $invoices): array
    {
        return array_values(array_map(fn (CompanyInvoice $invoice): array => $this->row($invoice), $invoices));
    }

    public function detail(CompanyInvoice $invoice, ?string $billingEmail = null): array
    {
        $row = $this->row($invoice);

        return $row + [
                'subtitle' => $this->summary($invoice),
                'period' => $invoice->getPeriodStart() !== null && $invoice->getPeriodEnd() !== null ? PeriodHelper::label($invoice->getPeriodStart(), $invoice->getPeriodEnd()) : null,
                'due' => $invoice->getType() === InvoiceType::CREDIT_NOTE ? '—' : PeriodHelper::day($invoice->getDueAt()),
                'lines' => array_values(array_map(static fn (CompanyInvoiceLine $line): array => [
                    'label' => $line->getLabel(),
                    'qty' => (string) $line->getQuantity(),
                    'unit' => MoneyHelper::formatExact($line->getUnitPrice()),
                    'total' => MoneyHelper::formatExact($line->getTotal()),
                ], $invoice->getLines()->toArray())),
                'ht' => MoneyHelper::formatExact($invoice->getTotalExcludingTax()),
                'vatLabel' => sprintf('TVA %d %%', $invoice->getTaxRate()),
                'vat' => MoneyHelper::formatExact($invoice->getTaxAmount()),
                'ttc' => MoneyHelper::formatExact($invoice->getTotalIncludingTax()),
                'stamp' => match ($row['kind']) {
                    'paid' => 'PAYÉE',
                    'refunded' => 'AVOIR',
                    'void' => 'ANNULÉE',
                    default => 'À PAYER',
                },
                'payment' => $this->paymentSentence($invoice),
                'billedTo' => [
                    'name' => $invoice->getBillingName(),
                    'address' => $invoice->getBillingAddress(),
                    'vatNumber' => $invoice->getBillingVatNumber(),
                    'email' => $billingEmail,
                ],
                'issuer' => $this->issuer,
                'creditedInvoice' => $invoice->getCreditedInvoice()?->getNumber(),
                'legalNotice' => self::LATE_FEE_NOTICE,
            ];
    }

    private function summary(CompanyInvoice $invoice): string
    {
        if ($invoice->getType() === InvoiceType::CREDIT_NOTE) {
            return sprintf('Avoir%s', $invoice->getCreditedInvoice() !== null ? ' sur la facture ' . $invoice->getCreditedInvoice()->getNumber() : '');
        }

        $period = $invoice->getPeriodStart() !== null ? PeriodHelper::monthName($invoice->getPeriodStart()) . ' ' . $invoice->getPeriodStart()->format('Y') : '';

        if ($invoice->getSubscription() === null) {
            $count = array_sum(array_map(static fn (CompanyInvoiceLine $line): int => $line->getQuantity(), $invoice->getLines()->toArray()));

            return sprintf('À la réservation, %d créneau%s réservé%s · %s', $count, $count > 1 ? 'x' : '', $count > 1 ? 's' : '', $period);
        }

        return sprintf('%s · %s', $invoice->getSubscription()->getPlan()?->getName(), $period);
    }

    private function paymentSentence(CompanyInvoice $invoice): ?string
    {
        if ($invoice->getType() === InvoiceType::CREDIT_NOTE) {
            return $invoice->getCreditedInvoice() !== null ? sprintf('Avoir rattaché à la facture %s.', $invoice->getCreditedInvoice()->getNumber()) : 'Avoir déduit de la prochaine facture.';
        }

        $succeeded = null;
        $failures = 0;

        foreach ($invoice->getPayments() as $payment) {
            if ($payment->getStatus() === PaymentStatus::SUCCEEDED) {
                $succeeded = $payment;
            } elseif ($payment->getStatus() === PaymentStatus::FAILED) {
                ++$failures;
            }
        }

        if ($invoice->getStatus() === InvoiceStatus::PAID && $succeeded !== null) {
            return sprintf('Payée le %s par %s%s.', PeriodHelper::day($succeeded->getSucceededAt()), PaymentMethodHelper::label($succeeded->getPaymentMethod()) ?? 'paiement en ligne', $failures > 0 ? sprintf(', après %d refus', $failures) : '');
        }

        if ($invoice->getStatus() === InvoiceStatus::PAID) {
            return 'Rien à régler.';
        }

        return $failures > 0 ? sprintf('Paiement refusé %d fois. À régler depuis votre espace.', $failures) : 'À régler à réception.';
    }
}