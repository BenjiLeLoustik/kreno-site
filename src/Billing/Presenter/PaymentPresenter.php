<?php

namespace App\Billing\Presenter;

use App\Billing\Enum\PaymentMethodType;
use App\Billing\Enum\PaymentStatus;
use App\Billing\Helper\PaymentMethodHelper;
use App\Billing\Helper\PeriodHelper;
use App\Billing\Service\PaymentService;
use App\Entity\Company\CompanyPayment;
use App\Entity\Company\CompanyPaymentMethod;
use App\Entity\Company\CompanySubscription;
use App\Shared\Helper\DateHelper;
use App\Shared\Helper\MoneyHelper;
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
            'note' => match (true) {
                $method->getType() === PaymentMethodType::SEPA_DEBIT => $method->getMandateAcceptedAt() !== null ? 'Mandat signé le ' . PeriodHelper::shortDay($method->getMandateAcceptedAt()) : 'Mandat SEPA',
                $expired => 'Expirée',
                default => 'Expire ' . PaymentMethodHelper::expiry($method),
            },
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
        $invoice = $payment->getInvoice();

        return [
            'id' => $payment->getId(),
            'status' => $payment->getStatus()->value,
            'statusLabel' => $payment->getStatus()->label(),
            'amount' => MoneyHelper::formatExact($payment->getAmount()) . ' TTC',
            'amountCents' => $payment->getAmount(),
            'method' => PaymentMethodHelper::label($payment->getPaymentMethod()),
            'attemptedAt' => DateHelper::iso($payment->getAttemptedAt()),
            'date' => $payment->getAttemptedAt() !== null ? sprintf('%s, %s', PeriodHelper::day($payment->getAttemptedAt()), $payment->getAttemptedAt()->format('H:i')) : null,
            'reason' => $payment->getFailureMessage(),
            'nextRetryAt' => PeriodHelper::shortDay($payment->getNextRetryAt()),
            'invoice' => $invoice === null ? null : ['number' => $invoice->getNumber(), 'status' => $invoice->getStatus()->value],
        ];
    }

    public function result(CompanyPayment $payment, ?CompanySubscription $subscription, array $methods = []): array
    {
        $data = $this->payment($payment);
        $invoice = $payment->getInvoice();

        if ($payment->getStatus() === PaymentStatus::SUCCEEDED) {
            $data['facts'] = array_values(array_filter([
                ['label' => 'Montant', 'value' => $data['amount']],
                ['label' => 'Moyen de paiement', 'value' => $data['method']],
                ['label' => 'Date', 'value' => $data['date']],
                $subscription?->getPlan() !== null ? ['label' => 'Formule', 'value' => $subscription->getPlan()->getName()] : null,
                $invoice?->getPeriodStart() !== null && $invoice->getPeriodEnd() !== null ? ['label' => 'Période', 'value' => PeriodHelper::label($invoice->getPeriodStart(), $invoice->getPeriodEnd())] : null,
                $subscription?->getCurrentPeriodEnd() !== null && $subscription->getCancelAt() === null ? ['label' => 'Prochaine échéance', 'value' => PeriodHelper::shortDay($subscription->getCurrentPeriodEnd())] : null,
            ]));

            return $data;
        }

        $failedAt = $payment->getAttemptedAt();
        $graceUntil = $subscription?->getGraceUntil();
        $timeline = [];

        if ($failedAt !== null) {
            $timeline[] = ['date' => PeriodHelper::shortDay($failedAt), 'text' => 'Paiement refusé. E-mail envoyé au propriétaire et à la comptabilité.', 'tone' => 'danger'];
        }

        foreach (PaymentService::RETRY_DAYS as $days) {
            if ($failedAt !== null && ($graceUntil === null || $failedAt->modify(sprintf('+%d days', $days)) < $graceUntil)) {
                $timeline[] = ['date' => PeriodHelper::shortDay($failedAt->modify(sprintf('+%d days', $days))), 'text' => 'Nouvelle tentative automatique sur le moyen par défaut.', 'tone' => 'warning'];
            }
        }

        if ($graceUntil !== null) {
            $timeline[] = ['date' => PeriodHelper::shortDay($graceUntil->modify('-7 days')), 'text' => 'Dernier rappel par e-mail et dans votre espace.', 'tone' => 'warning'];
            $timeline[] = ['date' => PeriodHelper::shortDay($graceUntil), 'text' => 'Vos créneaux ne sont plus publiés jusqu\'au règlement. Vos données sont conservées.', 'tone' => 'muted'];
        }

        $data['timeline'] = $timeline;
        $data['retryOptions'] = array_values(array_map(fn (CompanyPaymentMethod $method): array => $this->method($method) + [
                'retryLabel' => $method->getType() === PaymentMethodType::SEPA_DEBIT ? sprintf('Payer %s par prélèvement', MoneyHelper::formatExact($payment->getAmount())) : sprintf('Réessayer le paiement de %s', MoneyHelper::formatExact($payment->getAmount())),
                'retryNote' => $method === $payment->getPaymentMethod() ? 'Refusée aujourd\'hui · réessayer la même carte' : ($method->getType() === PaymentMethodType::SEPA_DEBIT ? 'Débité sous 2 jours ouvrés, sans validation bancaire' : null),
            ], $methods));

        return $data;
    }
}