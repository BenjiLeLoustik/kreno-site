<?php

namespace App\Mail\Listener;

use App\Billing\Enum\InvoiceStatus;
use App\Billing\Enum\InvoiceType;
use App\Billing\Enum\PaymentStatus;
use App\Billing\Event\InvoiceIssuedEvent;
use App\Billing\Event\PaymentActionRequiredEvent;
use App\Billing\Event\PaymentFailedEvent;
use App\Billing\Event\PaymentMethodAddedEvent;
use App\Billing\Event\PaymentSucceededEvent;
use App\Billing\Event\PriceChangeAnnouncedEvent;
use App\Billing\Event\PriceChangeCancelledEvent;
use App\Billing\Event\SubscriptionCancellationRevokedEvent;
use App\Billing\Event\SubscriptionCancelledEvent;
use App\Billing\Event\SubscriptionChangedEvent;
use App\Billing\Event\SubscriptionPausedEvent;
use App\Billing\Event\SubscriptionStartedEvent;
use App\Billing\Event\SubscriptionSuspendedEvent;
use App\Billing\Helper\PeriodHelper;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyInvoice;
use App\Entity\Company\CompanyPayment;
use App\Entity\Company\CompanySubscription;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentStatus;
use App\Mail\Enum\MailSubject;
use App\Mail\Presenter\MailContextPresenter;
use App\Mail\Provider\RecipientProvider;
use App\Mail\Service\MailService;
use App\Shared\Helper\MoneyHelper;
use App\User\Helper\UserNameHelper;
use NeoPHP\Component\Event\Attribute\AsListener;

class BillingMailListener
{
    public const INCIDENT_STATUSES = [PaymentStatus::FAILED, PaymentStatus::REQUIRES_ACTION];

    public function __construct(
        private MailService $mail,
        private RecipientProvider $recipients,
        private MailContextPresenter $context,
    ) {
    }

    #[AsListener(SubscriptionStartedEvent::class)]
    public function onSubscriptionStarted(SubscriptionStartedEvent $event): void
    {
        $subscription = $event->getSubscription();
        $name = $this->displayName($subscription->getCompany());
        $subject = MailSubject::SUBSCRIPTION_STARTED->text($name);

        $this->sendForSubscription($subscription, $subject, 'pro-subscription-started', [
            'establishment' => $name,
            'slotUrl' => $this->mail->url('pro_slot_new'),
        ]);
    }

    #[AsListener(SubscriptionChangedEvent::class)]
    public function onSubscriptionChanged(SubscriptionChangedEvent $event): void
    {
        $subscription = $event->getSubscription();
        $subject = MailSubject::SUBSCRIPTION_CHANGED->text($subscription->getPlan()?->getName());

        $this->sendForSubscription($subscription, $subject, 'pro-subscription-changed', [
            'previous' => $this->subscriptionContext($event->getPrevious()),
            'changedBy' => $this->fullName($subscription->getChangedBy()),
        ]);
    }

    #[AsListener(SubscriptionCancelledEvent::class)]
    public function onSubscriptionCancelled(SubscriptionCancelledEvent $event): void
    {
        $subscription = $event->getSubscription();
        $subject = MailSubject::SUBSCRIPTION_CANCELLED->text();

        $this->sendForSubscription($subscription, $subject, 'pro-subscription-cancelled', [
            'reason' => $subscription->getCancellationReason(),
        ]);
    }

    #[AsListener(SubscriptionCancellationRevokedEvent::class)]
    public function onCancellationRevoked(SubscriptionCancellationRevokedEvent $event): void
    {
        $subject = MailSubject::SUBSCRIPTION_REVOKED->text();

        $this->sendForSubscription($event->getSubscription(), $subject, 'pro-subscription-revoked');
    }

    #[AsListener(SubscriptionPausedEvent::class)]
    public function onSubscriptionPaused(SubscriptionPausedEvent $event): void
    {
        $subscription = $event->getSubscription();
        $subject = MailSubject::SUBSCRIPTION_PAUSED->text(PeriodHelper::day($subscription->getPausedUntil()));

        $this->sendForSubscription($subscription, $subject, 'pro-subscription-paused');
    }

    #[AsListener(SubscriptionSuspendedEvent::class)]
    public function onSubscriptionSuspended(SubscriptionSuspendedEvent $event): void
    {
        $subject = MailSubject::SUBSCRIPTION_SUSPENDED->text();

        $this->sendForSubscription($event->getSubscription(), $subject, 'pro-subscription-suspended', [
            'billingUrl' => $this->mail->url('pro_billing'),
        ]);
    }

    #[AsListener(InvoiceIssuedEvent::class)]
    public function onInvoiceIssued(InvoiceIssuedEvent $event): void
    {
        $invoice = $event->getInvoice();
        $context = $this->context->invoice($invoice);

        if ($invoice->getType() === InvoiceType::CREDIT_NOTE) {
            $this->sendCreditNote($invoice, $context);

            return;
        }

        if ($invoice->getStatus() !== InvoiceStatus::PAID) {
            return;
        }

        $this->sendToBilling($invoice->getCompany(), MailSubject::INVOICE->text($context['month']), 'pro-invoice', [
            'invoice' => $context,
            'paymentMethod' => null,
        ]);
    }

    #[AsListener(PaymentSucceededEvent::class)]
    public function onPaymentSucceeded(PaymentSucceededEvent $event): void
    {
        $payment = $event->getPayment();
        $invoice = $payment->getInvoice();

        if ($invoice === null) {
            return;
        }

        $context = [
            'invoice' => $this->context->invoice($invoice),
            'amount' => MoneyHelper::formatExact($payment->getAmount()),
            'paymentMethod' => $this->context->paymentMethod($payment->getPaymentMethod()),
            'paidAt' => PeriodHelper::day($payment->getSucceededAt()),
        ];

        if ($payment->getInitiatedBy() === null && !$this->hadIncident($payment)) {
            $subject = MailSubject::INVOICE->text($context['invoice']['month']);

            $this->sendToBilling($invoice->getCompany(), $subject, 'pro-invoice', $context);

            return;
        }

        $subject = MailSubject::PAYMENT_CONFIRMED->text($invoice->getNumber());
        $context['subscription'] = $this->subscriptionContext($invoice->getSubscription());

        $this->sendToBilling($invoice->getCompany(), $subject, 'pro-payment-confirmed', $context);
    }

    #[AsListener(PaymentFailedEvent::class)]
    public function onPaymentFailed(PaymentFailedEvent $event): void
    {
        $payment = $event->getPayment();
        $invoice = $payment->getInvoice();

        if ($invoice === null || $payment->getInitiatedBy() !== null) {
            return;
        }

        $this->sendToBilling($invoice->getCompany(), MailSubject::PAYMENT_FAILED->text(), 'pro-payment-failed', [
            'invoice' => $this->context->invoice($invoice),
            'amount' => MoneyHelper::formatExact($payment->getAmount()),
            'paymentMethod' => $this->context->paymentMethod($payment->getPaymentMethod()),
            'failure' => $payment->getFailureMessage(),
            'final' => $event->isFinal(),
            'nextRetryAt' => PeriodHelper::day($payment->getNextRetryAt()),
            'graceUntil' => PeriodHelper::day($invoice->getSubscription()?->getGraceUntil()),
            'billingUrl' => $this->mail->url('pro_billing'),
        ]);
    }

    #[AsListener(PaymentActionRequiredEvent::class)]
    public function onPaymentActionRequired(PaymentActionRequiredEvent $event): void
    {
        $payment = $event->getPayment();
        $invoice = $payment->getInvoice();

        if ($invoice === null || $payment->getInitiatedBy() !== null) {
            return;
        }

        $subject = MailSubject::PAYMENT_ACTION_REQUIRED->text();

        $this->sendToBilling($invoice->getCompany(), $subject, 'pro-payment-action-required', [
            'invoice' => $this->context->invoice($invoice),
            'amount' => MoneyHelper::formatExact($payment->getAmount()),
            'paymentUrl' => $this->mail->url('pro_billing_payment', ['id' => $payment->getId()]),
        ]);
    }

    #[AsListener(PaymentMethodAddedEvent::class)]
    public function onPaymentMethodAdded(PaymentMethodAddedEvent $event): void
    {
        $method = $event->getPaymentMethod();
        $subject = MailSubject::PAYMENT_METHOD_ADDED->text();

        $this->sendToBilling($method->getCompany(), $subject, 'pro-payment-method-added', [
            'paymentMethod' => $this->context->paymentMethod($method),
            'default' => $method->isDefaultMethod(),
            'addedBy' => $this->fullName($method->getAddedBy()),
            'addedAt' => PeriodHelper::day($method->getCreatedAt()),
            'billingUrl' => $this->mail->url('pro_billing'),
        ]);
    }

    #[AsListener(PriceChangeAnnouncedEvent::class)]
    public function onPriceChangeAnnounced(PriceChangeAnnouncedEvent $event): void
    {
        $lower = $event->getNewPrice() < $event->getCurrentPrice();
        $effectiveAt = PeriodHelper::day($event->getEffectiveAt());
        $subject = $this->priceChangeSubject($event, $lower)->text($effectiveAt);

        $this->sendForSubscription($event->getSubscription(), $subject, 'pro-price-change', [
            'lower' => $lower,
            'currentPrice' => MoneyHelper::format($event->getCurrentPrice()),
            'newPrice' => MoneyHelper::format($event->getNewPrice()),
            'difference' => MoneyHelper::format(abs($event->getNewPrice() - $event->getCurrentPrice())),
            'effectiveAt' => $effectiveAt,
            'newPlan' => $event->getNewPlan()->getName(),
            'correction' => $this->correction($event),
        ]);
    }

    #[AsListener(PriceChangeCancelledEvent::class)]
    public function onPriceChangeCancelled(PriceChangeCancelledEvent $event): void
    {
        $subject = MailSubject::PRICE_CHANGE_CANCELLED->text();

        $this->sendForSubscription($event->getSubscription(), $subject, 'pro-price-change-cancelled', [
            'announcedPrice' => MoneyHelper::format($event->getAnnouncedPrice()),
            'effectiveAt' => PeriodHelper::day($event->getEffectiveAt()),
        ]);
    }

    private function sendCreditNote(CompanyInvoice $invoice, array $context): void
    {
        $subject = MailSubject::CREDIT_NOTE->text($invoice->getNumber());

        $this->sendToBilling($invoice->getCompany(), $subject, 'pro-credit-note', [
            'invoice' => $context,
            'credited' => $invoice->getCreditedInvoice()?->getNumber(),
        ]);
    }

    private function sendForSubscription(
        CompanySubscription $subscription,
        string $subject,
        string $template,
        array $context = [],
    ): void {
        $context += [
            'subscription' => $this->context->subscription($subscription),
            'company' => $subscription->getCompany()?->getName(),
            'billingUrl' => $this->mail->url('pro_billing'),
        ];

        $this->sendToBilling($subscription->getCompany(), $subject, $template, $context);
    }

    private function sendToBilling(?Company $company, string $subject, string $template, array $context): void
    {
        $this->mail->send(MailService::PRO, $this->recipients->billing($company), $subject, $template, $context);
    }

    private function priceChangeSubject(PriceChangeAnnouncedEvent $event, bool $lower): MailSubject
    {
        if ($event->isCorrection()) {
            return MailSubject::PRICE_CORRECTION;
        }

        if ($lower) {
            return MailSubject::PRICE_DECREASE;
        }

        return MailSubject::PRICE_INCREASE;
    }

    private function correction(PriceChangeAnnouncedEvent $event): ?array
    {
        if (!$event->isCorrection()) {
            return null;
        }

        return [
            'newPrice' => MoneyHelper::format((int) $event->getPreviousNewPrice()),
            'effectiveAt' => PeriodHelper::day($event->getPreviousEffectiveAt()),
        ];
    }

    private function subscriptionContext(?CompanySubscription $subscription): ?array
    {
        if ($subscription === null) {
            return null;
        }

        return $this->context->subscription($subscription);
    }

    private function fullName(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        return UserNameHelper::fullName($user);
    }

    private function hadIncident(CompanyPayment $payment): bool
    {
        foreach ($payment->getInvoice()?->getPayments() ?? [] as $other) {
            if ($other !== $payment && in_array($other->getStatus(), self::INCIDENT_STATUSES, true)) {
                return true;
            }
        }

        return false;
    }

    private function displayName(?Company $company): ?string
    {
        foreach ($company?->getEstablishments() ?? [] as $establishment) {
            if ($establishment->getStatus() === EstablishmentStatus::ONLINE) {
                return $establishment->getName();
            }
        }

        return $company?->getName();
    }
}