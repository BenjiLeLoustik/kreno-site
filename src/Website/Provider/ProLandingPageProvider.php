<?php

namespace App\Website\Provider;

use App\Billing\Enum\BillingPeriod;
use App\Billing\Presenter\PlanPresenter;
use App\Billing\Service\PlanCatalog;
use App\Company\Provider\CurrentCompanyProvider;
use App\Entity\User\User;
use App\Faq\Enum\FaqAudience;
use App\Faq\Provider\FaqProvider;
use App\Website\Enum\ProLandingStep;

class ProLandingPageProvider
{
    public function __construct(
        private PlanCatalog $planCatalog,
        private PlanPresenter $planPresenter,
        private SiteRulesProvider $rules,
        private CurrentCompanyProvider $context,
        private FaqProvider $faq,
    ) {
    }

    public function index(?BillingPeriod $period, mixed $user): array
    {
        $period ??= BillingPeriod::MONTHLY;
        $plans = $this->planCatalog->plans();
        $rules = $this->rules->rules();

        return [
            'period' => $period->value,
            'plans' => $this->planPresenter->offers($plans, $period),
            'periods' => $this->planPresenter->periods($plans, $period),
            'steps' => ProLandingStep::all($rules),
            'faq' => $this->faq->questions(FaqAudience::PRO, $rules),
            'isPro' => $user instanceof User && $this->context->getMembership($user) !== null,
        ];
    }
}