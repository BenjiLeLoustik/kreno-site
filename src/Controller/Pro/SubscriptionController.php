<?php

namespace App\Controller\Pro;

use App\Billing\Dto\PlanChoiceInput;
use App\Billing\Dto\RetentionOfferInput;
use App\Billing\Enum\RetentionOffer;
use App\Billing\Enum\SubscriptionFlash;
use App\Billing\Mapper\SubscriptionRequestMapper;
use App\Billing\Model\SubscriptionChange;
use App\Billing\Provider\SubscriptionPageProvider;
use App\Billing\Service\PaymentActionStore;
use App\Billing\Service\RenewalService;
use App\Billing\Service\SubscriptionService;
use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Provider\ProContextProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class SubscriptionController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        ProContextProvider $pro,
        private SubscriptionService $subscriptionService,
        private RenewalService $renewals,
        private PaymentActionStore $actions,
        private SubscriptionPageProvider $pages,
    ) {
        parent::__construct($context, $resolver, $pro);
    }

    #[Route('/pro/subscription', name: 'pro_subscription', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $company = $this->permittedCompany(Permission::COMPANY_BILLING);
        $page = $this->pages->index($company, SubscriptionRequestMapper::period($request));

        return $this->renderPro('pro/subscription/index.html.twig', $page);
    }

    #[Route('/pro/subscription/change', name: 'pro_subscription_quote', methods: ['GET'])]
    public function quote(Request $request): Response
    {
        $company = $this->permittedCompany(Permission::COMPANY_BILLING);
        $page = null;
        $found = $this->attempt(function () use ($company, $request, &$page): void {
            $page = $this->pages->quote(
                $company,
                SubscriptionRequestMapper::plan($request),
                SubscriptionRequestMapper::promoCode($request),
                SubscriptionRequestMapper::period($request),
            );
        });

        if (!$found) {
            return $this->redirectToRoute('pro_subscription');
        }

        return $this->renderPro('pro/subscription/quote.html.twig', $page);
    }

    #[Csrf('pro')]
    #[Route('/pro/subscription', name: 'pro_subscription_change', methods: ['POST'])]
    public function change(#[MapRequestPayload(validate: false)] PlanChoiceInput $input): RedirectResponse
    {
        $company = $this->permittedCompany(Permission::COMPANY_BILLING);
        $input = SubscriptionRequestMapper::change($input);
        $change = null;

        if (!$this->invalid($input)) {
            $this->attempt(function () use ($company, $input, &$change): void {
                $change = $this->subscriptionService->change($company, $this->currentUser(), $input);
            });
        }

        if (!$change instanceof SubscriptionChange) {
            return $this->redirectToRoute('pro_subscription_quote', SubscriptionRequestMapper::quoteQuery($input));
        }

        $attempt = $change->getAttempt();

        if ($attempt !== null) {
            $this->actions->remember($attempt);

            return $this->redirectToRoute('pro_billing_payment', ['id' => $attempt->getPayment()->getId()]);
        }

        $this->addFlash('success', $this->pages->changeFlash($change));

        return $this->redirectToRoute('pro_subscription');
    }

    #[Route('/pro/subscription/cancel', name: 'pro_subscription_cancel_form', methods: ['GET'])]
    public function cancelForm(): Response
    {
        $subscription = $this->pages->current($this->permittedCompany(Permission::COMPANY_BILLING));

        return $this->renderPro('pro/subscription/cancel.html.twig', $this->pages->cancelForm($subscription));
    }

    #[Csrf('pro')]
    #[Route('/pro/subscription/cancel', name: 'pro_subscription_cancel', methods: ['POST'])]
    public function cancel(Request $request): RedirectResponse
    {
        $subscription = $this->pages->current($this->permittedCompany(Permission::COMPANY_BILLING));
        $input = SubscriptionRequestMapper::cancel($request);

        if (!$this->invalid($input) && $this->attempt(
                fn () => $this->subscriptionService->cancel($subscription, $this->currentUser(), $input),
            )) {
            $this->addFlash('success', $this->pages->cancelFlash($subscription));

            return $this->redirectToRoute('pro_subscription');
        }

        return $this->redirectToRoute('pro_subscription_cancel_form');
    }

    #[Csrf('pro')]
    #[Route('/pro/subscription/cancel/revoke', name: 'pro_subscription_cancel_revoke', methods: ['POST'])]
    public function revoke(): RedirectResponse
    {
        $subscription = $this->pages->current($this->permittedCompany(Permission::COMPANY_BILLING));
        $this->attempt(
            fn () => $this->subscriptionService->revokeCancellation($subscription),
            SubscriptionFlash::CANCELLATION_REVOKED->text(),
        );

        return $this->redirectToRoute('pro_subscription');
    }

    #[Csrf('pro')]
    #[Route('/pro/subscription/offer', name: 'pro_subscription_offer', methods: ['POST'])]
    public function offer(#[MapRequestPayload(validate: false)] RetentionOfferInput $input): RedirectResponse
    {
        $subscription = $this->pages->current($this->permittedCompany(Permission::COMPANY_BILLING));

        if (!$this->invalid($input) && $this->attempt(
                fn () => $this->renewals->applyOffer($subscription, $this->currentUser(), $input),
                RetentionOffer::messageFor($input->offer),
            )) {
            return $this->redirectToRoute('pro_subscription');
        }

        return $this->redirectToRoute('pro_subscription_cancel_form');
    }
}