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
use App\Entity\Company\CompanyPayment;
use App\Entity\Company\CompanySubscription;
use App\Establishment\Enum\EstablishmentStatus;
use App\Mail\Presenter\MailContextPresenter;
use App\Mail\Provider\RecipientProvider;
use App\Mail\Service\MailService;
use App\Shared\Helper\MoneyHelper;
use App\User\Helper\UserNameHelper;
use NeoPHP\Component\Event\Attribute\AsListener;

class BillingMailListener
{
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

        $this->sendForSubscription($subscription, sprintf('%s est en ligne, votre abonnement est actif', $this->displayName($subscription->getCompany())), 'pro-subscription-started', [
            'establishment' => $this->displayName($subscription->getCompany()),
            'slotUrl' => $this->mail->url('pro_slot_new'),
        ]);
    }

    #[AsListener(SubscriptionChangedEvent::class)]
    public function onSubscriptionChanged(SubscriptionChangedEvent $event): void
    {
        $subscription = $event->getSubscription();
        $previous = $event->getPrevious();

        $this->sendForSubscription($subscription, sprintf('Votre formule %s est active', $subscription->getPlan()?->getName()), 'pro-subscription-changed', [
            'previous' => $previous !== null ? $this->context->subscription($previous) : null,
            'changedBy' => $subscription->getChangedBy() !== null ? UserNameHelper::fullName($subscription->getChangedBy()) : null,
        ]);
    }

    #[AsListener(SubscriptionCancelledEvent::class)]
    public function onSubscriptionCancelled(SubscriptionCancelledEvent $event): void
    {
        $subscription = $event->getSubscription();

        $this->sendForSubscription($subscription, 'Votre résiliation Kréno Pro est enregistrée', 'pro-subscription-cancelled', [
            'reason' => $subscription->getCancellationReason(),
        ]);
    }

    #[AsListener(SubscriptionCancellationRevokedEvent::class)]
    public function onCancellationRevoked(SubscriptionCancellationRevokedEvent $event): void
    {
        $this->sendForSubscription($event->getSubscription(), 'Votre abonnement Kréno Pro continue', 'pro-subscription-revoked');
    }

    #[AsListener(SubscriptionPausedEvent::class)]
    public function onSubscriptionPaused(SubscriptionPausedEvent $event): void
    {
        $subscription = $event->getSubscription();

        $this->sendForSubscription($subscription, sprintf('Votre abonnement Kréno Pro est en pause jusqu’au %s', PeriodHelper::day($subscription->getPausedUntil())), 'pro-subscription-paused');
    }

    #[AsListener(SubscriptionSuspendedEvent::class)]
    public function onSubscriptionSuspended(SubscriptionSuspendedEvent $event): void
    {
        $this->sendForSubscription($event->getSubscription(), 'Vos créneaux Kréno ne sont plus publiés : paiement en attente', 'pro-subscription-suspended', [
            'billingUrl' => $this->mail->url('pro_billing'),
        ]);
    }

    #[AsListener(InvoiceIssuedEvent::class)]
    public function onInvoiceIssued(InvoiceIssuedEvent $event): void
    {
        $invoice = $event->getInvoice();
        $context = $this->context->invoice($invoice);

        if ($invoice->getType() === InvoiceType::CREDIT_NOTE) {
            $this->mail->send(MailService::PRO, $this->recipients->billing($invoice->getCompany()), sprintf('Avoir %s sur votre compte Kréno Pro', $invoice->getNumber()), 'pro-credit-note', [
                'invoice' => $context,
                'credited' => $invoice->getCreditedInvoice()?->getNumber(),
            ]);

            return;
        }

        if ($invoice->getStatus() === InvoiceStatus::PAID) {
            $this->mail->send(MailService::PRO, $this->recipients->billing($invoice->getCompany()), sprintf('Votre facture Kréno Pro de %s', $context['month']), 'pro-invoice', [
                'invoice' => $context,
                'paymentMethod' => null,
            ]);
        }
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

        if ($payment->getInitiatedBy() !== null || $this->hadIncident($payment)) {
            $this->mail->send(MailService::PRO, $this->recipients->billing($invoice->getCompany()), sprintf('Paiement reçu : facture %s réglée', $invoice->getNumber()), 'pro-payment-confirmed', $context + [
                    'subscription' => $invoice->getSubscription() !== null ? $this->context->subscription($invoice->getSubscription()) : null,
                ]);

            return;
        }

        $this->mail->send(MailService::PRO, $this->recipients->billing($invoice->getCompany()), sprintf('Votre facture Kréno Pro de %s', $context['invoice']['month']), 'pro-invoice', $context);
    }

    #[AsListener(PaymentFailedEvent::class)]
    public function onPaymentFailed(PaymentFailedEvent $event): void
    {
        $payment = $event->getPayment();
        $invoice = $payment->getInvoice();

        if ($invoice === null || $payment->getInitiatedBy() !== null) {
            return;
        }

        $subscription = $invoice->getSubscription();

        $this->mail->send(MailService::PRO, $this->recipients->billing($invoice->getCompany()), 'Action requise : votre paiement Kréno Pro a échoué', 'pro-payment-failed', [
            'invoice' => $this->context->invoice($invoice),
            'amount' => MoneyHelper::formatExact($payment->getAmount()),
            'paymentMethod' => $this->context->paymentMethod($payment->getPaymentMethod()),
            'failure' => $payment->getFailureMessage(),
            'final' => $event->isFinal(),
            'nextRetryAt' => PeriodHelper::day($payment->getNextRetryAt()),
            'graceUntil' => PeriodHelper::day($subscription?->getGraceUntil()),
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

        $this->mail->send(MailService::PRO, $this->recipients->billing($invoice->getCompany()), 'Action requise : confirmez votre paiement Kréno Pro', 'pro-payment-action-required', [
            'invoice' => $this->context->invoice($invoice),
            'amount' => MoneyHelper::formatExact($payment->getAmount()),
            'paymentUrl' => $this->mail->url('pro_billing_payment', ['id' => $payment->getId()]),
        ]);
    }

    #[AsListener(PaymentMethodAddedEvent::class)]
    public function onPaymentMethodAdded(PaymentMethodAddedEvent $event): void
    {
        $method = $event->getPaymentMethod();

        $this->mail->send(MailService::PRO, $this->recipients->billing($method->getCompany()), 'Nouveau moyen de paiement enregistré sur Kréno Pro', 'pro-payment-method-added', [
            'paymentMethod' => $this->context->paymentMethod($method),
            'default' => $method->isDefaultMethod(),
            'addedBy' => $method->getAddedBy() !== null ? UserNameHelper::fullName($method->getAddedBy()) : null,
            'addedAt' => PeriodHelper::day($method->getCreatedAt()),
            'billingUrl' => $this->mail->url('pro_billing'),
        ]);
    }

    #[AsListener(PriceChangeAnnouncedEvent::class)]
    public function onPriceChangeAnnounced(PriceChangeAnnouncedEvent $event): void
    {
        $subscription = $event->getSubscription();
        $lower = $event->getNewPrice() < $event->getCurrentPrice();
        $effectiveAt = PeriodHelper::day($event->getEffectiveAt());

        $subject = match (true) {
            $event->isCorrection() => sprintf('Mise à jour : nouveau prix de votre abonnement Kréno Pro au %s', $effectiveAt),
            $lower => sprintf('Bonne nouvelle : votre abonnement baisse le %s', $effectiveAt),
            default => sprintf('Évolution du prix de votre abonnement Kréno Pro au %s', $effectiveAt),
        };

        $this->sendForSubscription($subscription, $subject, 'pro-price-change', [
            'lower' => $lower,
            'currentPrice' => MoneyHelper::format($event->getCurrentPrice()),
            'newPrice' => MoneyHelper::format($event->getNewPrice()),
            'difference' => MoneyHelper::format(abs($event->getNewPrice() - $event->getCurrentPrice())),
            'effectiveAt' => $effectiveAt,
            'newPlan' => $event->getNewPlan()->getName(),
            'correction' => $event->isCorrection() ? [
                'newPrice' => MoneyHelper::format((int) $event->getPreviousNewPrice()),
                'effectiveAt' => PeriodHelper::day($event->getPreviousEffectiveAt()),
            ] : null,
        ]);
    }

    #[AsListener(PriceChangeCancelledEvent::class)]
    public function onPriceChangeCancelled(PriceChangeCancelledEvent $event): void
    {
        $subscription = $event->getSubscription();

        $this->sendForSubscription($subscription, 'Annulation : le prix de votre abonnement Kréno Pro ne change pas', 'pro-price-change-cancelled', [
            'announcedPrice' => MoneyHelper::format($event->getAnnouncedPrice()),
            'effectiveAt' => PeriodHelper::day($event->getEffectiveAt()),
        ]);
    }

    private function sendForSubscription(CompanySubscription $subscription, string $subject, string $template, array $context = []): void
    {
        $this->mail->send(MailService::PRO, $this->recipients->billing($subscription->getCompany()), $subject, $template, $context + [
                'subscription' => $this->context->subscription($subscription),
                'company' => $subscription->getCompany()?->getName(),
                'billingUrl' => $this->mail->url('pro_billing'),
            ]);
    }

    private function hadIncident(CompanyPayment $payment): bool
    {
        foreach ($payment->getInvoice()?->getPayments() ?? [] as $other) {
            if ($other !== $payment && in_array($other->getStatus(), [PaymentStatus::FAILED, PaymentStatus::REQUIRES_ACTION], true)) {
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