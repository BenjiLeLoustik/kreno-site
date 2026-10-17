<?php

namespace App\Billing\Provider;

use App\Billing\Enum\BillingGesture;
use App\Billing\Enum\BillingPeriod;
use App\Billing\Enum\ChangeApplication;
use App\Billing\Helper\PeriodHelper;
use App\Billing\Presenter\AdminBillingPresenter;
use App\Billing\Presenter\PlanPresenter;
use App\Billing\Service\PlanCatalog;
use App\Company\Exception\CompanyNotFoundException;
use App\Entity\Company\Company;
use App\Repository\Company\CompanyRepository;
use App\Repository\Company\CompanySubscriptionRepository;
use DateTimeImmutable;

class AdminBillingPageProvider
{
    public const MONTHS = 6;

    public function __construct(
        private CompanyRepository $companies,
        private CompanySubscriptionRepository $subscriptions,
        private PlanCatalog $catalog,
        private BillingKpiProvider $kpis,
        private AdminBillingPresenter $presenter,
        private PlanPresenter $planPresenter,
    ) {
    }

    public function index(?DateTimeImmutable $month, ?Company $company = null): array
    {
        $now = new DateTimeImmutable();
        $month ??= $now->modify('first day of this month')->setTime(0, 0);
        $subscriptions = $this->subscriptions->findAllCurrent();

        return [
            'month' => $month->format('Y-m'),
            'months' => $this->months($now),
            'kpis' => $this->kpis->admin($month),
            'rows' => $this->presenter->rows($subscriptions),
            'company' => $this->companySummary($company),
            'selected' => $this->selected($company, $subscriptions),
            'plans' => $this->planPresenter->offers($this->catalog->plans(), BillingPeriod::MONTHLY),
            'applications' => $this->applications(),
            'gestures' => $this->gestures(),
        ];
    }

    public function company(int $id): Company
    {
        $company = $this->companies->find($id);

        if (!$company instanceof Company) {
            throw new CompanyNotFoundException();
        }

        return $company;
    }

    private function companySummary(?Company $company): ?array
    {
        if ($company === null) {
            return null;
        }

        return ['id' => $company->getId(), 'name' => $company->getName()];
    }

    private function selected(?Company $company, array $subscriptions): ?array
    {
        if ($company !== null) {
            return $this->companyDetail($company);
        }

        foreach ($subscriptions as $subscription) {
            if ($subscription->getCompany() !== null && $this->presenter->row($subscription)['isFailed']) {
                return $this->presenter->detail($subscription->getCompany(), $subscription);
            }
        }

        return null;
    }

    private function companyDetail(Company $company): ?array
    {
        $subscription = $this->subscriptions->findCurrent($company);

        if ($subscription === null) {
            return null;
        }

        return $this->presenter->detail($company, $subscription);
    }

    private function months(DateTimeImmutable $now): array
    {
        $months = [];

        for ($i = 0; $i < self::MONTHS; ++$i) {
            $date = $now->modify(sprintf('first day of -%d month', $i))->setTime(0, 0);
            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => ucfirst(PeriodHelper::monthName($date)) . ' ' . $date->format('Y'),
            ];
        }

        return $months;
    }

    private function applications(): array
    {
        $applications = [];

        foreach (ChangeApplication::cases() as $application) {
            $applications[] = ['key' => $application->value, 'label' => $application->label()];
        }

        return $applications;
    }

    private function gestures(): array
    {
        $gestures = [];

        foreach (BillingGesture::cases() as $gesture) {
            $gestures[] = ['key' => $gesture->value, 'label' => $gesture->label()];
        }

        return $gestures;
    }
}