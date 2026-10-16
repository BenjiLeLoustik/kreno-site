<?php

namespace App\Controller\Admin;

use App\Billing\Dto\AdminSubscriptionInput;
use App\Billing\Dto\GraceInput;
use App\Billing\Enum\BillingGesture;
use App\Billing\Enum\BillingPeriod;
use App\Billing\Enum\ChangeApplication;
use App\Billing\Enum\PaymentStatus;
use App\Billing\Helper\PeriodHelper;
use App\Billing\Presenter\AdminBillingPresenter;
use App\Billing\Presenter\PlanPresenter;
use App\Billing\Provider\BillingKpiProvider;
use App\Billing\Service\AdminBillingService;
use App\Billing\Service\PlanCatalog;
use App\Entity\Company\Company;
use App\Repository\Company\CompanyRepository;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Shared\Exception\BusinessRuleException;
use DateTimeImmutable;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class BillingController extends AbstractAdminController
{
    public const MONTHS = 6;

    public function __construct(
        private CompanyRepository $companies,
        private CompanySubscriptionRepository $subscriptions,
        private AdminBillingService $billing,
        private PlanCatalog $catalog,
        private BillingKpiProvider $kpis,
        private AdminBillingPresenter $presenter,
        private PlanPresenter $planPresenter,
    ) {
    }

    #[Route('/admin/billing', name: 'admin_billing', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->currentAdmin();

        return $this->page($request, null);
    }

    #[Route('/admin/billing/companies/{id}', name: 'admin_billing_company', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Request $request, int $id): Response
    {
        $this->currentAdmin();

        return $this->page($request, $this->findCompany($id));
    }

    #[Csrf('admin_billing')]
    #[Route('/admin/billing/companies/{id}/retry', name: 'admin_billing_retry', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function retry(int $id): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $company = $this->findCompany($id);

        try {
            $status = $this->billing->retry($company, $admin)?->getPayment()->getStatus() ?? PaymentStatus::SUCCEEDED;
            $status === PaymentStatus::SUCCEEDED
                ? $this->addFlash('success', 'Prélèvement réussi. La facture est réglée.')
                : $this->addFlash('warning', sprintf('Prélèvement relancé : %s.', mb_strtolower($status->label())));
        } catch (BusinessRuleException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('admin_billing_company', ['id' => $id]);
    }

    #[Csrf('admin_billing')]
    #[Route('/admin/billing/companies/{id}/grace', name: 'admin_billing_grace', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function grace(int $id, #[MapRequestPayload(validate: false)] GraceInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $company = $this->findCompany($id);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->billing->grant($company, $admin, $input), sprintf('Délai de %d jours accordé à %s.', $input->days, $company->getName()));
        }

        return $this->redirectToRoute('admin_billing_company', ['id' => $id]);
    }

    #[Csrf('admin_billing')]
    #[Route('/admin/billing/companies/{id}/subscription', name: 'admin_billing_subscription', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function subscription(int $id, #[MapRequestPayload(validate: false)] AdminSubscriptionInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $company = $this->findCompany($id);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->billing->change($company, $admin, $input), 'Abonnement modifié. L\'entreprise a été prévenue par e-mail et l\'action est notée dans le journal.');
        }

        return $this->redirectToRoute('admin_billing_company', ['id' => $id]);
    }

    private function page(Request $request, ?Company $company): Response
    {
        $now = new DateTimeImmutable();
        $month = DateTimeImmutable::createFromFormat('!Y-m', $request->query->getString('month')) ?: $now->modify('first day of this month')->setTime(0, 0);
        $subscriptions = $this->subscriptions->findAllCurrent();
        $rows = $this->presenter->rows($subscriptions);
        $selected = null;

        if ($company !== null) {
            $subscription = $this->subscriptions->findCurrent($company);
            $selected = $subscription !== null ? $this->presenter->detail($company, $subscription) : null;
        } else {
            foreach ($subscriptions as $subscription) {
                if ($subscription->getCompany() !== null && $this->presenter->row($subscription)['isFailed']) {
                    $selected = $this->presenter->detail($subscription->getCompany(), $subscription);
                    break;
                }
            }
        }

        $months = [];

        for ($i = 0; $i < self::MONTHS; ++$i) {
            $date = $now->modify(sprintf('first day of -%d month', $i))->setTime(0, 0);
            $months[] = ['value' => $date->format('Y-m'), 'label' => ucfirst(PeriodHelper::monthName($date)) . ' ' . $date->format('Y')];
        }

        return $this->render('admin/billing/index.html.twig', [
            'month' => $month->format('Y-m'),
            'months' => $months,
            'kpis' => $this->kpis->admin($month),
            'rows' => $rows,
            'company' => $company !== null ? ['id' => $company->getId(), 'name' => $company->getName()] : null,
            'selected' => $selected,
            'plans' => $this->planPresenter->offers($this->catalog->plans(), BillingPeriod::MONTHLY),
            'applications' => array_map(
                static fn (ChangeApplication $application): array => ['key' => $application->value, 'label' => $application->label()],
                ChangeApplication::cases(),
            ),
            'gestures' => array_map(
                static fn (BillingGesture $gesture): array => ['key' => $gesture->value, 'label' => $gesture->label()],
                BillingGesture::cases(),
            ),
        ]);
    }

    private function findCompany(int $id): Company
    {
        $company = $this->companies->find($id);

        return $company instanceof Company ? $company : throw $this->createNotFoundException('Entreprise introuvable.');
    }
}