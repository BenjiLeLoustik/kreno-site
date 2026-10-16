<?php

namespace App\Billing\Service;

use App\Billing\Enum\InvoiceStatus;
use App\Billing\Enum\PaymentStatus;
use App\Billing\Enum\SubscriptionStatus;
use App\Billing\Event\PaymentActionRequiredEvent;
use App\Billing\Event\PaymentFailedEvent;
use App\Billing\Event\PaymentSucceededEvent;
use App\Billing\Gateway\PaymentGatewayInterface;
use App\Billing\Model\GatewayPaymentResult;
use App\Billing\Model\PaymentAttempt;
use App\Entity\Company\CompanyInvoice;
use App\Entity\Company\CompanyPayment;
use App\Entity\Company\CompanyPaymentMethod;
use App\Entity\Company\CompanySubscription;
use App\Entity\User\User;
use App\Repository\Company\CompanyPaymentMethodRepository;
use App\Repository\Company\CompanyPaymentRepository;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Shared\Exception\BusinessRuleException;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class PaymentService
{
    public const RETRY_DAYS = [3, 7];

    public const GRACE_DAYS = 17;

    public function __construct(
        private OrmManagerInterface $orm,
        private EventManagerInterface $events,
        private PaymentGatewayInterface $gateway,
        private CompanyPaymentRepository $payments,
        private CompanyPaymentMethodRepository $methods,
        private CompanySubscriptionRepository $subscriptions,
    ) {
    }

    public function collect(CompanyInvoice $invoice, ?CompanyPaymentMethod $method = null, ?User $initiatedBy = null, bool $offSession = true): ?PaymentAttempt
    {
        if (in_array($invoice->getStatus(), [InvoiceStatus::PAID, InvoiceStatus::VOID], true)) {
            throw new BusinessRuleException('Cette facture est déjà réglée.');
        }

        $company = $invoice->getCompany() ?? throw new BusinessRuleException('Facture sans entreprise.');

        if ($method !== null && $method->getCompany() !== $company) {
            throw new BusinessRuleException('Ce moyen de paiement n\'appartient pas à l\'entreprise.');
        }

        $method ??= $this->methods->findDefault($company);
        $now = new DateTimeImmutable();

        $payment = new CompanyPayment();
        $payment->setPaymentMethod($method);
        $payment->setInitiatedBy($initiatedBy);
        $payment->setAmount($invoice->getTotalIncludingTax());
        $payment->setStatus(PaymentStatus::PENDING);
        $payment->setAttemptedAt($now);
        $invoice->addPayment($payment);
        $this->orm->persist($payment);
        $this->orm->flush();

        if ($method === null) {
            $this->apply($payment, new GatewayPaymentResult(null, PaymentStatus::FAILED, null, 'missing_payment_method', 'aucun moyen de paiement enregistré'));

            return new PaymentAttempt($payment);
        }

        $result = $this->gateway->charge($method, $payment->getAmount(), sprintf('Facture %s', $invoice->getNumber()), sprintf('kreno-payment-%d', $payment->getId()), $offSession);

        return new PaymentAttempt($this->apply($payment, $result), $result->getClientSecret());
    }

    public function confirm(CompanyPayment $payment): CompanyPayment
    {
        if ($payment->getProviderReference() === null) {
            return $payment;
        }

        return $this->apply($payment, $this->gateway->retrieveCharge($payment->getProviderReference()));
    }

    public function handleWebhook(string $payload, string $signature): ?CompanyPayment
    {
        $result = $this->gateway->parseWebhook($payload, $signature);

        if ($result === null || $result->getReference() === null) {
            return null;
        }

        $payment = $this->payments->findOneByProviderReference($result->getReference());

        if ($payment === null || $payment->getStatus() === $result->getStatus() || $payment->getStatus() === PaymentStatus::SUCCEEDED) {
            return $payment;
        }

        return $this->apply($payment, $result);
    }

    public function retryDue(?DateTimeImmutable $now = null): int
    {
        $count = 0;

        foreach ($this->payments->findRetryDue($now ?? new DateTimeImmutable()) as $payment) {
            $payment->setNextRetryAt(null);
            $this->orm->flush();
            $invoice = $payment->getInvoice();

            if ($invoice !== null && in_array($invoice->getStatus(), [InvoiceStatus::OPEN, InvoiceStatus::FAILED], true)) {
                $this->collect($invoice);
                ++$count;
            }
        }

        return $count;
    }

    public function apply(CompanyPayment $payment, GatewayPaymentResult $result): CompanyPayment
    {
        $now = new DateTimeImmutable();
        $invoice = $payment->getInvoice();
        $subscription = $this->subscriptionOf($invoice);

        if ($result->getReference() !== null) {
            $payment->setProviderReference($result->getReference());
        }

        $payment->setStatus($result->getStatus());

        if ($result->getStatus() === PaymentStatus::SUCCEEDED) {
            $payment->setSucceededAt($now);
            $payment->setFailureCode(null);
            $payment->setFailureMessage(null);
            $invoice?->setStatus(InvoiceStatus::PAID);
            $invoice?->setPaidAt($now);

            foreach ($invoice?->getPayments() ?? [] as $other) {
                $other->setNextRetryAt(null);
            }

            if ($subscription !== null && $subscription->getStatus() === SubscriptionStatus::PAST_DUE && $this->allPaid($subscription)) {
                $subscription->setStatus(SubscriptionStatus::ACTIVE);
                $subscription->setGraceUntil(null);
            }

            $this->orm->flush();
            $this->events->dispatch(new PaymentSucceededEvent($payment));

            return $payment;
        }

        if ($result->getStatus() === PaymentStatus::PENDING) {
            $this->orm->flush();

            return $payment;
        }

        $this->markPastDue($subscription, $now);

        if ($result->getStatus() === PaymentStatus::REQUIRES_ACTION) {
            $invoice?->setStatus(InvoiceStatus::FAILED);
            $this->orm->flush();
            $this->events->dispatch(new PaymentActionRequiredEvent($payment));

            return $payment;
        }

        $payment->setFailureCode($result->getFailureCode());
        $payment->setFailureMessage($result->getFailureMessage());
        $invoice?->setStatus(InvoiceStatus::FAILED);

        foreach ($invoice?->getPayments() ?? [] as $other) {
            $other->setNextRetryAt(null);
        }

        $failures = $invoice !== null ? $this->payments->countFailedForInvoice($invoice) + 1 : 1;
        $retryDays = self::RETRY_DAYS[$failures - 1] ?? null;
        $payment->setNextRetryAt($retryDays !== null && $payment->getInitiatedBy() === null ? $now->modify(sprintf('+%d days', $retryDays)) : null);

        $this->orm->flush();
        $this->events->dispatch(new PaymentFailedEvent($payment, $retryDays === null));

        return $payment;
    }

    private function markPastDue(?CompanySubscription $subscription, DateTimeImmutable $now): void
    {
        if ($subscription === null || !in_array($subscription->getStatus(), [SubscriptionStatus::ACTIVE, SubscriptionStatus::PAST_DUE], true)) {
            return;
        }

        if ($subscription->getStatus() === SubscriptionStatus::ACTIVE) {
            $subscription->setStatus(SubscriptionStatus::PAST_DUE);
            $subscription->setGraceUntil($now->modify(sprintf('+%d days', self::GRACE_DAYS)));
        }
    }

    private function subscriptionOf(?CompanyInvoice $invoice): ?CompanySubscription
    {
        if ($invoice === null) {
            return null;
        }

        $subscription = $invoice->getSubscription();

        if ($subscription !== null && in_array($subscription->getStatus(), CompanySubscriptionRepository::CURRENT_STATUSES, true)) {
            return $subscription;
        }

        return $invoice->getCompany() !== null ? $this->subscriptions->findCurrent($invoice->getCompany()) : null;
    }

    private function allPaid(CompanySubscription $subscription): bool
    {
        foreach ($subscription->getCompany()?->getInvoices() ?? [] as $invoice) {
            if (in_array($invoice->getStatus(), [InvoiceStatus::OPEN, InvoiceStatus::FAILED], true)) {
                return false;
            }
        }

        return true;
    }
}