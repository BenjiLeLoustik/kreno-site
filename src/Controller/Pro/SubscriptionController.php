<?php

namespace App\Controller\Pro;

use App\Billing\Dto\PlanChoiceInput;
use App\Billing\Dto\RetentionOfferInput;
use App\Billing\Dto\SubscriptionCancelInput;
use App\Billing\Enum\BillingPeriod;
use App\Billing\Model\SubscriptionChange;
use App\Billing\Presenter\PaymentPresenter;
use App\Billing\Presenter\PlanPresenter;
use App\Billing\Presenter\SubscriptionPresenter;
use App\Billing\Service\PlanCatalog;
use App\Billing\Service\PriceCalculator;
use App\Billing\Service\PromoCodeService;
use App\Billing\Service\RenewalService;
use App\Billing\Service\SubscriptionService;
use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Company\Company;
use App\Entity\Company\CompanySubscription;
use App\Entity\Website\WebsitePlan;
use App\Entity\Website\WebsitePromoCode;
use App\Repository\Company\CompanyPaymentMethodRepository;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Shared\Exception\BusinessRuleException;
use App\Website\Enum\PlanBilling;
use DateTimeImmutable;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;
use NeoPHP\Component\Session\SessionManagerInterface;

class SubscriptionController extends AbstractProController
{
    public const OFFERS = [
        'price' => 'La remise est appliquée à votre abonnement.',
        'volume' => 'Votre formule passera à la réservation à la prochaine échéance.',
        'pause' => 'Votre abonnement est mis en pause.',
    ];

    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        private SubscriptionService $subscriptionService,
        private RenewalService $renewals,
        private PlanCatalog $catalog,
        private PriceCalculator $prices,
        private PromoCodeService $promos,
        private SessionManagerInterface $session,
        private CompanySubscriptionRepository $subscriptions,
        private CompanyPaymentMethodRepository $methods,
        private PlanPresenter $planPresenter,
        private SubscriptionPresenter $subscriptionPresenter,
        private PaymentPresenter $paymentPresenter,
    ) {
        parent::__construct($context, $resolver);
    }

    #[Route('/pro/subscription', name: 'pro_subscription', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $company = $this->billingCompany();
        $current = $this->subscriptions->findCurrent($company);
        $period = BillingPeriod::tryFrom($request->query->getString('period')) ?? $current?->getPeriod() ?? BillingPeriod::MONTHLY;
        $plans = $this->catalog->plans();

        return $this->render('pro/subscription/index.html.twig', [
            'context' => $this->proContext(),
            'current' => $current !== null ? $this->subscriptionPresenter->summary($current) : null,
            'period' => $period->value,
            'periods' => $this->planPresenter->periods($plans, $period),
            'plans' => $this->planPresenter->offers($plans, $period, $current, $current?->getPlan()?->getCode()),
            'establishmentsCount' => count($this->subscriptionService->billableEstablishments($company)),
        ]);
    }

    #[Route('/pro/subscription/change', name: 'pro_subscription_quote', methods: ['GET'])]
    public function quote(Request $request): Response
    {
        $company = $this->billingCompany();
        $code = $request->query->getString('plan');
        $promoCode = trim($request->query->getString('promoCode')) ?: null;
        $current = $this->subscriptions->findCurrent($company);
        $promoError = null;

        try {
            $plan = $this->catalog->get($code);
        } catch (BusinessRuleException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('pro_subscription');
        }

        $period = $plan->getBilling() === PlanBilling::PER_BOOKING ? BillingPeriod::MONTHLY : (BillingPeriod::tryFrom($request->query->getString('period')) ?? BillingPeriod::MONTHLY);

        try {
            $promo = $this->promos->resolve($promoCode, $plan);
        } catch (BusinessRuleException $exception) {
            $promo = null;
            $promoError = $exception->getMessage();
        }

        $establishments = count($this->subscriptionService->billableEstablishments($company));

        return $this->render('pro/subscription/quote.html.twig', [
            'context' => $this->proContext(),
            'current' => $current !== null ? $this->subscriptionPresenter->summary($current) : null,
            'plan' => $this->planPresenter->offer($plan, $period, $current),
            'period' => $period->value,
            'quote' => $this->prices->quote($plan, $period, $promo),
            'promoCode' => $promoCode,
            'promo' => $promo !== null ? ['code' => $promo->getCode(), 'label' => $this->promos->describe($promo)] : null,
            'promoError' => $promoError,
            'prorata' => $current !== null && $plan->getBilling() === PlanBilling::SUBSCRIPTION ? $this->prorata($current, $plan, $period, $promo) : null,
            'scheduled' => $current !== null && $plan->getBilling() === PlanBilling::PER_BOOKING,
            'uncoveredEstablishments' => $plan->getBilling() === PlanBilling::SUBSCRIPTION && $plan->getMaxEstablishments() !== null ? max(0, $establishments - $plan->getMaxEstablishments()) : 0,
            'paymentMethods' => array_values(array_filter($this->paymentPresenter->methods($this->methods->findForCompany($company)), static fn (array $method): bool => !$method['expired'])),
        ]);
    }

    #[Csrf('pro')]
    #[Route('/pro/subscription', name: 'pro_subscription_change', methods: ['POST'])]
    public function change(#[MapRequestPayload(validate: false)] PlanChoiceInput $input): RedirectResponse
    {
        $company = $this->billingCompany();
        $input->promoCode = $input->promoCode !== null && trim($input->promoCode) !== '' ? trim($input->promoCode) : null;
        $change = null;

        if (!$this->invalid($input)) {
            $this->attempt(function () use ($company, $input, &$change): void {
                $change = $this->subscriptionService->change($company, $this->currentUser(), $input);
            });
        }

        if (!$change instanceof SubscriptionChange) {
            return $this->redirectToRoute('pro_subscription_quote', ['plan' => $input->plan, 'period' => $input->period, 'promoCode' => $input->promoCode]);
        }

        $attempt = $change->getAttempt();

        if ($attempt !== null) {
            $payment = $attempt->getPayment();

            if ($attempt->requiresAction() && $attempt->getClientSecret() !== null) {
                $actions = $this->session->get(BillingController::PAYMENT_ACTIONS);
                $actions = is_array($actions) ? $actions : [];
                $actions[$payment->getId()] = $attempt->getClientSecret();
                $this->session->set(BillingController::PAYMENT_ACTIONS, $actions);
            }

            return $this->redirectToRoute('pro_billing_payment', ['id' => $payment->getId()]);
        }

        $summary = $this->subscriptionPresenter->summary($change->getSubscription());
        $this->addFlash('success', $change->isScheduled()
            ? sprintf('Changement enregistré : il prendra effet le %s.', $summary['nextDate'] ?? 'à la prochaine échéance')
            : sprintf('Vous êtes maintenant sur la formule %s.', $summary['plan']['name']));

        return $this->redirectToRoute('pro_subscription');
    }

    #[Route('/pro/subscription/cancel', name: 'pro_subscription_cancel_form', methods: ['GET'])]
    public function cancelForm(): Response
    {
        $subscription = $this->currentSubscription();

        return $this->render('pro/subscription/cancel.html.twig', [
            'context' => $this->proContext(),
            'current' => $this->subscriptionPresenter->summary($subscription),
            'cancellation' => $this->subscriptionPresenter->cancellation($subscription),
        ]);
    }

    #[Csrf('pro')]
    #[Route('/pro/subscription/cancel', name: 'pro_subscription_cancel', methods: ['POST'])]
    public function cancel(Request $request): RedirectResponse
    {
        $subscription = $this->currentSubscription();
        $input = new SubscriptionCancelInput();
        $input->reason = $request->request->getString('reason') ?: null;
        $input->comment = trim($request->request->getString('comment')) ?: null;
        $input->acknowledged = $request->request->getBoolean('acknowledged');

        if (!$this->invalid($input) && $this->attempt(fn () => $this->subscriptionService->cancel($subscription, $this->currentUser(), $input))) {
            $this->addFlash('success', sprintf('Résiliation enregistrée. Votre formule reste active jusqu\'au %s.', $this->subscriptionPresenter->summary($subscription)['cancelAt'] ?? 'terme de la période'));

            return $this->redirectToRoute('pro_subscription');
        }

        return $this->redirectToRoute('pro_subscription_cancel_form');
    }

    #[Csrf('pro')]
    #[Route('/pro/subscription/cancel/revoke', name: 'pro_subscription_cancel_revoke', methods: ['POST'])]
    public function revoke(): RedirectResponse
    {
        $subscription = $this->currentSubscription();
        $this->attempt(fn () => $this->subscriptionService->revokeCancellation($subscription), 'Résiliation annulée : votre abonnement continue.');

        return $this->redirectToRoute('pro_subscription');
    }

    #[Csrf('pro')]
    #[Route('/pro/subscription/offer', name: 'pro_subscription_offer', methods: ['POST'])]
    public function offer(#[MapRequestPayload(validate: false)] RetentionOfferInput $input): RedirectResponse
    {
        $subscription = $this->currentSubscription();

        if (!$this->invalid($input) && $this->attempt(fn () => $this->renewals->applyOffer($subscription, $this->currentUser(), $input), self::OFFERS[$input->offer] ?? null)) {
            return $this->redirectToRoute('pro_subscription');
        }

        return $this->redirectToRoute('pro_subscription_cancel_form');
    }

    private function prorata(CompanySubscription $current, WebsitePlan $plan, BillingPeriod $period, ?WebsitePromoCode $promo): array
    {
        $prorata = $this->prices->prorata($current, $plan, $period, $promo, new DateTimeImmutable());

        return [
            'until' => $prorata['periodEnd']->format('d/m/Y'),
            'keepsPeriodEnd' => $prorata['keepsPeriodEnd'],
            'charge' => $prorata['chargeLabel'],
            'credit' => $prorata['creditLabel'],
            'due' => $prorata['dueLabel'],
            'dueIsZero' => $prorata['dueIncludingTax'] === 0,
        ];
    }

    private function currentSubscription(): CompanySubscription
    {
        return $this->subscriptions->findCurrent($this->billingCompany()) ?? throw $this->createNotFoundException('Aucun abonnement en cours.');
    }

    private function billingCompany(): Company
    {
        $company = $this->currentCompany();
        $this->requirePermission(Permission::COMPANY_BILLING, $company);

        return $company;
    }
}