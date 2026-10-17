<?php

namespace App\Controller\Admin;

use App\Shared\Mapper\AdminQueryMapper;
use App\Website\Dto\PriceChangePolicyInput;
use App\Website\Dto\WebsitePlanInput;
use App\Website\Dto\WebsitePromoCodeInput;
use App\Website\Enum\PricingFlash;
use App\Website\Provider\AdminPricingPageProvider;
use App\Website\Provider\WebsiteVersionProvider;
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
        private WebsiteVersionProvider $versions,
        private WebsitePricingService $pricing,
        private AdminPricingPageProvider $pages,
    ) {
    }

    #[Route('/admin/pricing', name: 'admin_pricing_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->currentAdmin();
        $page = $this->pages->index(AdminQueryMapper::optional($request, 'version'));

        return $this->render('admin/pricing/index.html.twig', $page);
    }

    #[Csrf('admin_pricing')]
    #[Route('/admin/pricing/{code}/plans', name: 'admin_pricing_plan_create', methods: ['POST'])]
    public function createPlan(
        string $code,
        #[MapRequestPayload(validate: false)] WebsitePlanInput $input,
    ): RedirectResponse {
        $admin = $this->currentAdmin();
        $website = $this->versions->find($code);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->pricing->createPlan($website, $input, $admin),
                PricingFlash::PLAN_CREATED->text($input->name),
            );
        }

        return $this->redirectToRoute('admin_pricing_index', ['version' => $code]);
    }

    #[Csrf('admin_pricing')]
    #[Route('/admin/pricing/{code}/plans/{planCode}', name: 'admin_pricing_plan_update', methods: ['POST'])]
    public function updatePlan(
        string $code,
        string $planCode,
        #[MapRequestPayload(validate: false)] WebsitePlanInput $input,
    ): RedirectResponse {
        $admin = $this->currentAdmin();
        $website = $this->versions->find($code);
        $plan = $this->pages->plan($website, $planCode);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->pricing->updatePlan($website, $plan, $input, $admin),
                PricingFlash::PLAN_UPDATED->text($plan->getName()),
            );
        }

        return $this->redirectToRoute('admin_pricing_index', ['version' => $code]);
    }

    #[Csrf('admin_pricing')]
    #[Route('/admin/pricing/{code}/promo-codes', name: 'admin_pricing_promo_create', methods: ['POST'])]
    public function createPromoCode(
        string $code,
        #[MapRequestPayload(validate: false)] WebsitePromoCodeInput $input,
    ): RedirectResponse {
        $admin = $this->currentAdmin();
        $website = $this->versions->find($code);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->pricing->createPromoCode($website, $input, $admin),
                PricingFlash::PROMO_CREATED->text($input->code),
            );
        }

        return $this->redirectToRoute('admin_pricing_index', ['version' => $code]);
    }

    #[Csrf('admin_pricing')]
    #[Route('/admin/pricing/{code}/promo-codes/{promoCode}', name: 'admin_pricing_promo_update', methods: ['POST'])]
    public function updatePromoCode(
        string $code,
        string $promoCode,
        #[MapRequestPayload(validate: false)] WebsitePromoCodeInput $input,
    ): RedirectResponse {
        $admin = $this->currentAdmin();
        $website = $this->versions->find($code);
        $promo = $this->pages->promoCode($website, $promoCode);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->pricing->updatePromoCode($website, $promo, $input, $admin),
                PricingFlash::PROMO_UPDATED->text($promo->getCode()),
            );
        }

        return $this->redirectToRoute('admin_pricing_index', ['version' => $code]);
    }

    #[Csrf('admin_pricing')]
    #[Route('/admin/pricing/{code}/policy', name: 'admin_pricing_policy', methods: ['POST'])]
    public function updatePolicy(
        string $code,
        #[MapRequestPayload(validate: false)] PriceChangePolicyInput $input,
    ): RedirectResponse {
        $admin = $this->currentAdmin();
        $website = $this->versions->find($code);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->pricing->updatePolicy($website, $input, $admin),
                PricingFlash::POLICY_SAVED->text(),
            );
        }

        return $this->redirectToRoute('admin_pricing_index', ['version' => $code]);
    }
}