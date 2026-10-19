<?php

namespace App\Controller\Admin;

use App\Billing\Dto\AdminSubscriptionInput;
use App\Billing\Dto\GraceInput;
use App\Billing\Enum\AdminBillingFlash;
use App\Billing\Enum\PaymentStatus;
use App\Billing\Mapper\AdminBillingRequestMapper;
use App\Billing\Provider\AdminBillingPageProvider;
use App\Billing\Service\AdminBillingRetryService;
use App\Billing\Service\AdminBillingService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class BillingController extends AbstractAdminController
{
    public function __construct(
        private AdminBillingService $billing,
        private AdminBillingRetryService $retries,
        private AdminBillingPageProvider $pages,
    ) {
    }

    #[Route('/admin/billing', name: 'admin_billing', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->currentAdmin();
        $page = $this->pages->index(AdminBillingRequestMapper::month($request));

        return $this->render('admin/billing/index.html.twig', $page);
    }

    #[Route(
        '/admin/billing/companies/{id}',
        name: 'admin_billing_company',
        methods: ['GET'],
        requirements: ['id' => '\d+'],
    )]
    public function show(Request $request, int $id): Response
    {
        $this->currentAdmin();
        $page = $this->pages->index(AdminBillingRequestMapper::month($request), $this->pages->company($id));

        return $this->render('admin/billing/index.html.twig', $page);
    }

    #[Csrf('admin_billing')]
    #[Route(
        '/admin/billing/companies/{id}/retry',
        name: 'admin_billing_retry',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function retry(int $id): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $company = $this->pages->company($id);
        $status = null;
        $this->attempt(function () use ($company, $admin, &$status): void {
            $status = $this->retries->retry($company, $admin);
        });

        if ($status instanceof PaymentStatus) {
            $flash = AdminBillingFlash::retry($status);
            $this->addFlash($flash->type(), $flash->text(mb_strtolower($status->label())));
        }

        return $this->redirectToRoute('admin_billing_company', ['id' => $id]);
    }

    #[Csrf('admin_billing')]
    #[Route(
        '/admin/billing/companies/{id}/grace',
        name: 'admin_billing_grace',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function grace(int $id, #[MapRequestPayload(validate: false)] GraceInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $company = $this->pages->company($id);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->billing->grant($company, $admin, $input),
                AdminBillingFlash::GRACE_GRANTED->text($input->days, $company->getName()),
            );
        }

        return $this->redirectToRoute('admin_billing_company', ['id' => $id]);
    }

    #[Csrf('admin_billing')]
    #[Route(
        '/admin/billing/companies/{id}/subscription',
        name: 'admin_billing_subscription',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function subscription(
        int $id,
        #[MapRequestPayload(validate: false)] AdminSubscriptionInput $input,
    ): RedirectResponse {
        $admin = $this->currentAdmin();
        $company = $this->pages->company($id);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->billing->change($company, $admin, $input),
                AdminBillingFlash::SUBSCRIPTION_CHANGED->text(),
            );
        }

        return $this->redirectToRoute('admin_billing_company', ['id' => $id]);
    }
}