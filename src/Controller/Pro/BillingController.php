<?php

namespace App\Controller\Pro;

use App\Billing\Dto\BillingInfoInput;
use App\Billing\Dto\PaymentSetupInput;
use App\Billing\Enum\BillingFlash;
use App\Billing\Helper\InvoiceNumberHelper;
use App\Billing\Mapper\BillingRequestMapper;
use App\Billing\Provider\BillingPageProvider;
use App\Billing\Provider\PaymentSetupProvider;
use App\Billing\Service\BillingInfoService;
use App\Billing\Service\InvoicePaymentService;
use App\Billing\Service\PaymentActionStore;
use App\Billing\Service\PaymentMethodService;
use App\Billing\Service\PaymentService;
use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Provider\ProContextProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Shared\Helper\JsonStatusHelper;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\JsonResponse;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class BillingController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        ProContextProvider $pro,
        private BillingInfoService $billingInfo,
        private PaymentMethodService $methodService,
        private PaymentService $payments,
        private InvoicePaymentService $invoicePayments,
        private PaymentActionStore $actions,
        private PaymentSetupProvider $setups,
        private BillingPageProvider $pages,
    ) {
        parent::__construct($context, $resolver, $pro);
    }

    #[Route('/pro/billing', name: 'pro_billing', methods: ['GET'])]
    public function index(): Response
    {
        $company = $this->permittedCompany(Permission::COMPANY_BILLING);

        return $this->renderPro('pro/billing/index.html.twig', $this->pages->index($company));
    }

    #[Csrf('pro')]
    #[Route('/pro/billing/info', name: 'pro_billing_info', methods: ['POST'])]
    public function info(#[MapRequestPayload(validate: false)] BillingInfoInput $input): RedirectResponse
    {
        $company = $this->permittedCompany(Permission::COMPANY_BILLING);
        $input = BillingRequestMapper::info($input);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->billingInfo->update($company, $input), BillingFlash::INFO_SAVED->text());
        }

        return $this->redirect($this->generateUrl('pro_billing') . '#informations');
    }

    #[Csrf('pro')]
    #[Route('/pro/billing/payment-methods/setup', name: 'pro_billing_method_setup', methods: ['POST'])]
    public function setup(#[MapRequestPayload(validate: false)] PaymentSetupInput $input): JsonResponse
    {
        $company = $this->permittedCompany(Permission::COMPANY_BILLING);
        $body = $this->setups->start($company, BillingRequestMapper::setupType($input));

        return $this->json($body, JsonStatusHelper::of($body));
    }

    #[Csrf('pro')]
    #[Route('/pro/billing/payment-methods', name: 'pro_billing_method_add', methods: ['POST'])]
    public function addMethod(Request $request): RedirectResponse
    {
        $company = $this->permittedCompany(Permission::COMPANY_BILLING);
        $input = BillingRequestMapper::paymentMethod($request);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->methodService->add($company, $this->currentUser(), $input),
                BillingFlash::METHOD_SAVED->text(),
            );
        }

        return $this->redirect($this->generateUrl('pro_billing') . '#moyens');
    }

    #[Csrf('pro')]
    #[Route(
        '/pro/billing/payment-methods/{id}/default',
        name: 'pro_billing_method_default',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function defaultMethod(int $id): RedirectResponse
    {
        $company = $this->permittedCompany(Permission::COMPANY_BILLING);
        $method = $this->pages->method($company, $id);
        $this->attempt(
            fn () => $this->methodService->setDefault($company, $method),
            BillingFlash::METHOD_DEFAULT->text(),
        );

        return $this->redirect($this->generateUrl('pro_billing') . '#moyens');
    }

    #[Csrf('pro')]
    #[Route(
        '/pro/billing/payment-methods/{id}/delete',
        name: 'pro_billing_method_delete',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function deleteMethod(int $id): RedirectResponse
    {
        $company = $this->permittedCompany(Permission::COMPANY_BILLING);
        $method = $this->pages->method($company, $id);
        $this->attempt(fn () => $this->methodService->remove($company, $method), BillingFlash::METHOD_DELETED->text());

        return $this->redirect($this->generateUrl('pro_billing') . '#moyens');
    }

    #[Route(
        '/pro/billing/invoices/{number}',
        name: 'pro_billing_invoice',
        methods: ['GET'],
        requirements: ['number' => InvoiceNumberHelper::PATTERN],
    )]
    public function invoice(string $number): Response
    {
        $company = $this->permittedCompany(Permission::COMPANY_BILLING);

        return $this->renderPro('pro/billing/invoice.html.twig', $this->pages->invoicePage($company, $number));
    }

    #[Csrf('pro')]
    #[Route(
        '/pro/billing/invoices/{number}/pay',
        name: 'pro_billing_invoice_pay',
        methods: ['POST'],
        requirements: ['number' => InvoiceNumberHelper::PATTERN],
    )]
    public function pay(Request $request, string $number): RedirectResponse
    {
        $company = $this->permittedCompany(Permission::COMPANY_BILLING);
        $invoice = $this->pages->invoice($company, $number);
        $method = $this->pages->chosenMethod($company, BillingRequestMapper::methodId($request));
        $makeDefault = BillingRequestMapper::makeDefault($request);
        $attempt = null;

        $this->attempt(fn () => $this->invoicePayments->preferMethod($company, $method, $makeDefault));
        $paid = $this->attempt(function () use ($invoice, $method, &$attempt): void {
            $attempt = $this->invoicePayments->pay($invoice, $method, $this->currentUser());
        });

        if (!$paid) {
            return $this->redirectToRoute('pro_billing_invoice', ['number' => $number]);
        }

        if ($attempt === null) {
            $this->addFlash('success', BillingFlash::INVOICE_PAID->text());

            return $this->redirectToRoute('pro_billing');
        }

        $this->actions->remember($attempt);

        return $this->redirectToRoute('pro_billing_payment', ['id' => $attempt->getPayment()->getId()]);
    }

    #[Route('/pro/billing/payments/{id}', name: 'pro_billing_payment', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function payment(int $id): Response
    {
        $company = $this->permittedCompany(Permission::COMPANY_BILLING);

        return $this->renderPro('pro/billing/payment.html.twig', $this->pages->paymentPage($company, $id));
    }

    #[Csrf('pro')]
    #[Route(
        '/pro/billing/payments/{id}/confirm',
        name: 'pro_billing_payment_confirm',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function confirm(int $id): RedirectResponse
    {
        $company = $this->permittedCompany(Permission::COMPANY_BILLING);
        $payment = $this->pages->payment($company, $id);
        $this->attempt(fn () => $this->payments->confirm($payment));
        $this->actions->forgetSettled($payment);

        return $this->redirectToRoute('pro_billing_payment', ['id' => $id]);
    }
}