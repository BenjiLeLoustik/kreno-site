<?php

namespace App\Billing\Service;

use App\Billing\Enum\PaymentStatus;
use App\Billing\Model\PaymentAttempt;
use App\Entity\Company\CompanyPayment;
use NeoPHP\Component\Session\SessionManagerInterface;

class PaymentActionStore
{
    public const PAYMENT_ACTIONS = 'pro.billing.actions';

    public function __construct(
        private SessionManagerInterface $session,
    ) {
    }

    public function remember(PaymentAttempt $attempt): void
    {
        if (!$attempt->requiresAction() || $attempt->getClientSecret() === null) {
            return;
        }

        $actions = $this->actions();
        $actions[$attempt->getPayment()->getId()] = $attempt->getClientSecret();
        $this->session->set(self::PAYMENT_ACTIONS, $actions);
    }

    public function secret(CompanyPayment $payment): ?string
    {
        if ($payment->getStatus() !== PaymentStatus::REQUIRES_ACTION) {
            return null;
        }

        $secret = $this->actions()[$payment->getId()] ?? null;

        if (!is_string($secret)) {
            return null;
        }

        return $secret;
    }

    public function forgetSettled(CompanyPayment $payment): void
    {
        $actions = $this->session->get(self::PAYMENT_ACTIONS);

        if (!is_array($actions) || $payment->getStatus() === PaymentStatus::REQUIRES_ACTION) {
            return;
        }

        unset($actions[$payment->getId()]);
        $this->session->set(self::PAYMENT_ACTIONS, $actions);
    }

    private function actions(): array
    {
        $actions = $this->session->get(self::PAYMENT_ACTIONS);

        if (!is_array($actions)) {
            return [];
        }

        return $actions;
    }
}