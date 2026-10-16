<?php

namespace App\Controller\Admin;

use App\Billing\Provider\BillingKpiProvider;
use App\Repository\Website\WebsiteConfigRepository;
use App\Repository\Website\WebsitePlanRepository;
use App\Repository\Website\WebsitePromoCodeRepository;
use App\Website\Dto\PriceChangePolicyInput;
use App\Website\Dto\WebsitePlanInput;
use App\Website\Dto\WebsitePromoCodeInput;
use App\Website\Enum\PlanBilling;
use App\Website\Enum\PriceChangePolicy;
use App\Website\Enum\WebsiteStatus;
use App\Website\Presenter\WebsiteConfigPresenter;
use App\Website\Presenter\WebsitePricingPresenter;
use App\Website\Provider\WebsiteProvider;
use App\Website\Service\WebsitePricingService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class PricingController extends AbstractAdminController
{
    public function __construct(
        private WebsiteProvider $provider,
        private WebsiteConfigRepository $websites,
        private WebsitePlanRepository $plans,
        private WebsitePromoCodeRepository $promoCodes,
        private WebsitePricingService $pricing,
        private WebsiteConfigPresenter $websitePresenter,
        private WebsitePricingPresenter $pricingPresenter,
        private BillingKpiProvider $kpis,
    ) {
    }

    #[Route('/admin/pricing', name: 'admin_pricing_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->currentAdmin();

        $website = $this->findVersion($this->provider, $request->query->getString('version') ?: null);

        return $this->render('admin/pricing/index.html.twig', [
            'versions' => $this->websitePresenter->summaries($this->websites->findAllForAdmin()),
            'selected' => $this->websitePresenter->summary($website),
            'pricing' => $this->pricingPresenter->present($website, $this->websites->findLive()),
            'kpis' => $website->getStatus() === WebsiteStatus::LIVE ? $this->kpis->pricing() : null,
            'billings' => array_map(static fn (PlanBilling $case): array => ['value' => $case->value, 'label' => $case->label()], PlanBilling::cases()),
            'policies' => array_map(static fn (PriceChangePolicy $case): array => ['value' => $case->value, 'label' => $case->label()], PriceChangePolicy::cases()),
        ]);
    }

    #[Csrf('admin_pricing')]
    #[Route('/admin/pricing/{code}/plans', name: 'admin_pricing_plan_create', methods: ['POST'])]
    public function createPlan(string $code, #[MapRequestPayload(validate: false)] WebsitePlanInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $website = $this->findVersion($this->provider, $code);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->pricing->createPlan($website, $input, $admin), sprintf('Formule « %s » ajoutée.', $input->name));
        }

        return $this->back($code);
    }

    #[Csrf('admin_pricing')]
    #[Route('/admin/pricing/{code}/plans/{planCode}', name: 'admin_pricing_plan_update', methods: ['POST'])]
    public function updatePlan(string $code, string $planCode, #[MapRequestPayload(validate: false)] WebsitePlanInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $website = $this->findVersion($this->provider, $code);
        $plan = $this->plans->findOneByWebsiteAndCode($website, $planCode) ?? throw $this->createNotFoundException('La formule {plan} n\'existe pas.', ['plan' => $planCode]);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->pricing->updatePlan($website, $plan, $input, $admin), sprintf('Formule « %s » modifiée.', $plan->getName()));
        }

        return $this->back($code);
    }

    #[Csrf('admin_pricing')]
    #[Route('/admin/pricing/{code}/promo-codes', name: 'admin_pricing_promo_create', methods: ['POST'])]
    public function createPromoCode(string $code, #[MapRequestPayload(validate: false)] WebsitePromoCodeInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $website = $this->findVersion($this->provider, $code);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->pricing->createPromoCode($website, $input, $admin), sprintf('Code %s créé.', $input->code));
        }

        return $this->back($code);
    }

    #[Csrf('admin_pricing')]
    #[Route('/admin/pricing/{code}/promo-codes/{promoCode}', name: 'admin_pricing_promo_update', methods: ['POST'])]
    public function updatePromoCode(string $code, string $promoCode, #[MapRequestPayload(validate: false)] WebsitePromoCodeInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $website = $this->findVersion($this->provider, $code);
        $promo = $this->promoCodes->findOneByWebsiteAndCode($website, $promoCode) ?? throw $this->createNotFoundException('Le code {promo} n\'existe pas.', ['promo' => $promoCode]);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->pricing->updatePromoCode($website, $promo, $input, $admin), sprintf('Code %s modifié.', $promo->getCode()));
        }

        return $this->back($code);
    }

    #[Csrf('admin_pricing')]
    #[Route('/admin/pricing/{code}/policy', name: 'admin_pricing_policy', methods: ['POST'])]
    public function updatePolicy(string $code, #[MapRequestPayload(validate: false)] PriceChangePolicyInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $website = $this->findVersion($this->provider, $code);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->pricing->updatePolicy($website, $input, $admin), 'Politique de changement de prix enregistrée.');
        }

        return $this->back($code);
    }

    private function back(string $code): RedirectResponse
    {
        return $this->redirectToRoute('admin_pricing_index', ['version' => $code]);
    }
}