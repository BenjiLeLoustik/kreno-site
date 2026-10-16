<?php

namespace App\Controller\Front;

use App\Billing\Service\PaymentService;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\JsonResponse;
use NeoPHP\Component\Routing\Attribute\Route;

class BillingWebhookController extends AbstractController
{
    public function __construct(
        private PaymentService $payments,
    ) {
    }

    #[Route('/billing/webhook', name: 'billing_webhook', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $payment = $this->payments->handleWebhook($request->getContent(), (string) $request->headers->get('Stripe-Signature', ''));

        return $this->json(['received' => true, 'payment' => $payment?->getId()]);
    }
}