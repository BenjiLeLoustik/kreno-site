<?php

namespace App\Billing\Service;

use App\Billing\Enum\InvoiceStatus;
use App\Billing\Enum\PaymentStatus;
use App\Billing\Enum\SubscriptionStatus;
use App\Billing\Event\PaymentActionRequiredEvent;
use App\Billing\Event\PaymentFailedEvent;
use App\Billing\Event\PaymentSucceededEvent;
use App\Billing\Exception\ForeignPaymentMethodException;
use App\Billing\Exception\InvoiceAlreadySettledException;
use App\Billing\Exception\InvoiceWithoutCompanyException;
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
use App\Shared\Helper\Translation;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class PaymentService
{
    public const RETRY_DAYS = [3, 7];

    public const GRACE_DAYS = 17;

    public const UNPAID_STATUSES = [
        InvoiceStatus::OPEN,
        InvoiceStatus::FAILED
    ];

    public function __construct(
        private OrmManagerInterface $orm,
        private EventManagerInterface $events,
        private PaymentGatewayInterface $gateway,
        private CompanyPaymentRepository $payments,
        private CompanyPaymentMethodRepository $methods,
        private CompanySubscriptionRepository $subscriptions,
    ) {
    }

    public function collect(
        CompanyInvoice $invoice,
        ?CompanyPaymentMethod $method = null,
        ?User $initiatedBy = null,
        bool $offSession = true,
    ): ?PaymentAttempt {
        if (in_array($invoice->getStatus(), [InvoiceStatus::PAID, InvoiceStatus::VOID], true)) {
            throw new InvoiceAlreadySettledException();
        }

        $company = $invoice->getCompany() ?? throw new InvoiceWithoutCompanyException();

        if ($method !== null && $method->getCompany() !== $company) {
            throw new ForeignPaymentMethodException();
        }

        $method ??= $this->methods->findDefault($company);
        $payment = $this->createPayment($invoice, $method, $initiatedBy);

        if ($method === null) {
            $this->apply($payment, $this->missingMethodResult());

            return new PaymentAttempt($payment);
        }

        $result = $this->gateway->charge(
            $method,
            $payment->getAmount(),
            sprintf(Translation::translate('Facture %s'), $invoice->getNumber()),
            sprintf('kreno-payment-%d', $payment->getId()),
            $offSession,
        );

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

        if ($payment === null || $payment->getStatus() === PaymentStatus::SUCCEEDED) {
            return $payment;
        }

        if ($payment->getStatus() === $result->getStatus()) {
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

            if ($invoice !== null && in_array($invoice->getStatus(), self::UNPAID_STATUSES, true)) {
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
            return $this->succeed($payment, $invoice, $subscription, $now);
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

        return $this->fail($payment, $invoice, $result, $now);
    }

    private function createPayment(
        CompanyInvoice $invoice,
        ?CompanyPaymentMethod $method,
        ?User $initiatedBy,
    ): CompanyPayment {
        $payment = new CompanyPayment();
        $payment->setPaymentMethod($method);
        $payment->setInitiatedBy($initiatedBy);
        $payment->setAmount($invoice->getTotalIncludingTax());
        $payment->setStatus(PaymentStatus::PENDING);
        $payment->setAttemptedAt(new DateTimeImmutable());
        $invoice->addPayment($payment);
        $this->orm->persist($payment);
        $this->orm->flush();

        return $payment;
    }

    private function missingMethodResult(): GatewayPaymentResult
    {
        return new GatewayPaymentResult(
            null,
            PaymentStatus::FAILED,
            null,
            'missing_payment_method',
            Translation::translate('aucun moyen de paiement enregistré'),
        );
    }

    private function succeed(
        CompanyPayment $payment,
        ?CompanyInvoice $invoice,
        ?CompanySubscription $subscription,
        DateTimeImmutable $now,
    ): CompanyPayment {
        $payment->setSucceededAt($now);
        $payment->setFailureCode(null);
        $payment->setFailureMessage(null);
        $invoice?->setStatus(InvoiceStatus::PAID);
        $invoice?->setPaidAt($now);
        $this->clearRetries($invoice);
        $this->reactivate($subscription);

        $this->orm->flush();
        $this->events->dispatch(new PaymentSucceededEvent($payment));

        return $payment;
    }

    private function fail(
        CompanyPayment $payment,
        ?CompanyInvoice $invoice,
        GatewayPaymentResult $result,
        DateTimeImmutable $now,
    ): CompanyPayment {
        $payment->setFailureCode($result->getFailureCode());
        $payment->setFailureMessage($result->getFailureMessage());
        $invoice?->setStatus(InvoiceStatus::FAILED);
        $this->clearRetries($invoice);

        $retryDays = self::RETRY_DAYS[$this->failureCount($invoice) - 1] ?? null;
        $payment->setNextRetryAt($this->nextRetryAt($payment, $retryDays, $now));

        $this->orm->flush();
        $this->events->dispatch(new PaymentFailedEvent($payment, $retryDays === null));

        return $payment;
    }

    private function clearRetries(?CompanyInvoice $invoice): void
    {
        foreach ($invoice?->getPayments() ?? [] as $payment) {
            $payment->setNextRetryAt(null);
        }
    }

    private function failureCount(?CompanyInvoice $invoice): int
    {
        if ($invoice === null) {
            return 1;
        }

        return $this->payments->countFailedForInvoice($invoice) + 1;
    }

    private function nextRetryAt(CompanyPayment $payment, ?int $retryDays, DateTimeImmutable $now): ?DateTimeImmutable
    {
        if ($retryDays === null || $payment->getInitiatedBy() !== null) {
            return null;
        }

        return $now->modify(sprintf('+%d days', $retryDays));
    }

    private function reactivate(?CompanySubscription $subscription): void
    {
        if ($subscription?->getStatus() !== SubscriptionStatus::PAST_DUE || !$this->allPaid($subscription)) {
            return;
        }

        $subscription->setStatus(SubscriptionStatus::ACTIVE);
        $subscription->setGraceUntil(null);
    }

    private function markPastDue(?CompanySubscription $subscription, DateTimeImmutable $now): void
    {
        if ($subscription?->getStatus() !== SubscriptionStatus::ACTIVE) {
            return;
        }

        $subscription->setStatus(SubscriptionStatus::PAST_DUE);
        $subscription->setGraceUntil($now->modify(sprintf('+%d days', self::GRACE_DAYS)));
    }

    private function subscriptionOf(?CompanyInvoice $invoice): ?CompanySubscription
    {
        if ($invoice === null) {
            return null;
        }

        $subscription = $invoice->getSubscription();

        if ($subscription !== null) {
            $current = in_array(
                $subscription->getStatus(),
                CompanySubscriptionRepository::CURRENT_STATUSES,
                true
            );

            if ($current) {
                return $subscription;
            }
        }

        $company = $invoice->getCompany();

        if ($company === null) {
            return null;
        }

        return $this->subscriptions->findCurrent($company);
    }

    private function allPaid(CompanySubscription $subscription): bool
    {
        foreach ($subscription->getCompany()?->getInvoices() ?? [] as $invoice) {
            if (in_array($invoice->getStatus(), self::UNPAID_STATUSES, true)) {
                return false;
            }
        }

        return true;
    }
}