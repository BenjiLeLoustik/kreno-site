<?php

namespace App\Billing\Presenter;

use App\Billing\Enum\PaymentMethodType;
use App\Billing\Enum\PaymentStatus;
use App\Billing\Helper\PaymentMethodHelper;
use App\Billing\Helper\PeriodHelper;
use App\Billing\Service\PaymentService;
use App\Entity\Company\CompanyInvoice;
use App\Entity\Company\CompanyPayment;
use App\Entity\Company\CompanyPaymentMethod;
use App\Entity\Company\CompanySubscription;
use App\Shared\Helper\DateHelper;
use App\Shared\Helper\MoneyHelper;
use App\Shared\Helper\Translation;
use DateTimeImmutable;

class PaymentPresenter
{
    public function method(CompanyPaymentMethod $method, ?DateTimeImmutable $now = null): array
    {
        $expired = PaymentMethodHelper::isExpired($method, $now);

        return [
            'id' => $method->getId(),
            'type' => $method->getType()?->value,
            'brand' => PaymentMethodHelper::brand($method),
            'label' => PaymentMethodHelper::label($method),
            'holderName' => $method->getHolderName(),
            'note' => $this->methodNote($method, $expired),
            'expired' => $expired,
            'isDefault' => $method->isDefaultMethod(),
            'canDefault' => !$method->isDefaultMethod() && !$expired,
        ];
    }

    public function methods(array $methods): array
    {
        return array_values(array_map(fn (CompanyPaymentMethod $method): array => $this->method($method), $methods));
    }

    public function payment(CompanyPayment $payment): array
    {
        return [
            'id' => $payment->getId(),
            'status' => $payment->getStatus()->value,
            'statusLabel' => $payment->getStatus()->label(),
            'amount' => MoneyHelper::formatExact($payment->getAmount()) . Translation::translate(' TTC'),
            'amountCents' => $payment->getAmount(),
            'method' => PaymentMethodHelper::label($payment->getPaymentMethod()),
            'attemptedAt' => DateHelper::iso($payment->getAttemptedAt()),
            'date' => $this->attemptDate($payment),
            'reason' => $payment->getFailureMessage(),
            'nextRetryAt' => PeriodHelper::shortDay($payment->getNextRetryAt()),
            'invoice' => $this->invoice($payment->getInvoice()),
        ];
    }

    public function result(CompanyPayment $payment, ?CompanySubscription $subscription, array $methods = []): array
    {
        $data = $this->payment($payment);

        if ($payment->getStatus() === PaymentStatus::SUCCEEDED) {
            $data['facts'] = $this->facts($data, $payment->getInvoice(), $subscription);

            return $data;
        }

        $data['timeline'] = $this->timeline($payment->getAttemptedAt(), $subscription?->getGraceUntil());
        $data['retryOptions'] = $this->retryOptions($payment, $methods);

        return $data;
    }

    private function methodNote(CompanyPaymentMethod $method, bool $expired): string
    {
        if ($method->getType() === PaymentMethodType::SEPA_DEBIT) {
            return $this->mandateNote($method);
        }

        if ($expired) {
            return Translation::translate('Expirée');
        }

        return Translation::translate('Expire {date}', ['date' => PaymentMethodHelper::expiry($method)]);
    }

    private function mandateNote(CompanyPaymentMethod $method): string
    {
        $acceptedAt = $method->getMandateAcceptedAt();

        if ($acceptedAt === null) {
            return Translation::translate('Mandat SEPA');
        }

        return Translation::translate('Mandat signé le {date}', ['date' => PeriodHelper::shortDay($acceptedAt)]);
    }

    private function attemptDate(CompanyPayment $payment): ?string
    {
        $attemptedAt = $payment->getAttemptedAt();

        if ($attemptedAt === null) {
            return null;
        }

        return sprintf('%s, %s', PeriodHelper::day($attemptedAt), $attemptedAt->format('H:i'));
    }

    private function invoice(?CompanyInvoice $invoice): ?array
    {
        if ($invoice === null) {
            return null;
        }

        return [
            'number' => $invoice->getNumber(),
            'status' => $invoice->getStatus()->value
        ];
    }

    private function facts(array $data, ?CompanyInvoice $invoice, ?CompanySubscription $subscription): array
    {
        $facts = [
            $this->fact(Translation::translate('Montant'), $data['amount']),
            $this->fact(Translation::translate('Moyen de paiement'), $data['method']),
            $this->fact(Translation::translate('Date'), $data['date']),
        ];

        $plan = $subscription?->getPlan();

        if ($plan !== null) {
            $facts[] = $this->fact(Translation::translate('Formule'), $plan->getName());
        }

        $start = $invoice?->getPeriodStart();
        $end = $invoice?->getPeriodEnd();

        if ($start !== null && $end !== null) {
            $facts[] = $this->fact(Translation::translate('Période'), PeriodHelper::label($start, $end));
        }

        $periodEnd = $subscription?->getCurrentPeriodEnd();

        if ($periodEnd !== null && $subscription->getCancelAt() === null) {
            $facts[] = $this->fact(Translation::translate('Prochaine échéance'), PeriodHelper::shortDay($periodEnd));
        }

        return $facts;
    }

    private function fact(string $label, ?string $value): array
    {
        return [
            'label' => $label,
            'value' => $value
        ];
    }

    private function timeline(?DateTimeImmutable $failedAt, ?DateTimeImmutable $graceUntil): array
    {
        $timeline = [];

        if ($failedAt !== null) {
            $refused = Translation::translate('Paiement refusé. E-mail envoyé au propriétaire et à la comptabilité.');
            $retry = Translation::translate('Nouvelle tentative automatique sur le moyen par défaut.');
            $timeline[] = $this->step($failedAt, $refused, 'danger');

            foreach ($this->retryDates($failedAt, $graceUntil) as $retryAt) {
                $timeline[] = $this->step($retryAt, $retry, 'warning');
            }
        }

        if ($graceUntil !== null) {
            $reminder = Translation::translate('Dernier rappel par e-mail et dans votre espace.');
            $suspended = Translation::translate('Vos créneaux ne sont plus publiés jusqu\'au règlement. Vos données sont conservées.');
            $timeline[] = $this->step($graceUntil->modify('-7 days'), $reminder, 'warning');
            $timeline[] = $this->step($graceUntil, $suspended, 'muted');
        }

        return $timeline;
    }

    private function retryDates(DateTimeImmutable $failedAt, ?DateTimeImmutable $graceUntil): array
    {
        $dates = [];

        foreach (PaymentService::RETRY_DAYS as $days) {
            $retryAt = $failedAt->modify(sprintf('+%d days', $days));

            if ($graceUntil === null || $retryAt < $graceUntil) {
                $dates[] = $retryAt;
            }
        }

        return $dates;
    }

    private function step(DateTimeImmutable $date, string $text, string $tone): array
    {
        return [
            'date' => PeriodHelper::shortDay($date),
            'text' => $text,
            'tone' => $tone
        ];
    }

    private function retryOptions(CompanyPayment $payment, array $methods): array
    {
        $options = [];

        foreach ($methods as $method) {
            $options[] = $this->method($method) + [
                    'retryLabel' => $this->retryLabel($method, $payment),
                    'retryNote' => $this->retryNote($method, $payment),
                ];
        }

        return $options;
    }

    private function retryLabel(CompanyPaymentMethod $method, CompanyPayment $payment): string
    {
        $amount = MoneyHelper::formatExact($payment->getAmount());

        if ($method->getType() === PaymentMethodType::SEPA_DEBIT) {
            return sprintf(
                Translation::translate('Payer %s par prélèvement'),
                $amount
            );
        }

        return sprintf(
            Translation::translate('Réessayer le paiement de %s'),
            $amount
        );
    }

    private function retryNote(CompanyPaymentMethod $method, CompanyPayment $payment): ?string
    {
        if ($method === $payment->getPaymentMethod()) {
            return Translation::translate('Refusée aujourd\'hui · réessayer la même carte');
        }

        if ($method->getType() === PaymentMethodType::SEPA_DEBIT) {
            return Translation::translate('Débité sous 2 jours ouvrés, sans validation bancaire');
        }

        return null;
    }
}