<?php

namespace App\Controller\Pro;

use App\Billing\Dto\BillingInfoInput;
use App\Billing\Dto\PaymentMethodInput;
use App\Billing\Dto\PaymentSetupInput;
use App\Billing\Enum\PaymentMethodType;
use App\Billing\Enum\PaymentStatus;
use App\Billing\Exception\PaymentGatewayException;
use App\Billing\Gateway\PaymentGatewayInterface;
use App\Billing\Helper\InvoiceNumberHelper;
use App\Billing\Model\PaymentAttempt;
use App\Billing\Presenter\InvoicePresenter;
use App\Billing\Presenter\PaymentPresenter;
use App\Billing\Presenter\SubscriptionPresenter;
use App\Billing\Service\BillingInfoService;
use App\Billing\Service\PaymentMethodService;
use App\Billing\Service\PaymentService;
use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyInvoice;
use App\Entity\Company\CompanyPaymentMethod;
use App\Repository\Company\CompanyInvoiceRepository;
use App\Repository\Company\CompanyPaymentMethodRepository;
use App\Repository\Company\CompanyPaymentRepository;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Shared\Exception\BusinessRuleException;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\JsonResponse;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;
use NeoPHP\Component\Session\SessionManagerInterface;

class BillingController extends AbstractProController
{
    public const PAYMENT_ACTIONS = 'pro.billing.actions';

    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        private PaymentGatewayInterface $gateway,
        private BillingInfoService $billingInfo,
        private PaymentMethodService $methodService,
        private PaymentService $payments,
        private SessionManagerInterface $session,
        private CompanySubscriptionRepository $subscriptions,
        private CompanyInvoiceRepository $invoices,
        private CompanyPaymentMethodRepository $methods,
        private CompanyPaymentRepository $paymentRepository,
        private SubscriptionPresenter $subscriptionPresenter,
        private InvoicePresenter $invoicePresenter,
        private PaymentPresenter $paymentPresenter,
    ) {
        parent::__construct($context, $resolver);
    }

    #[Route('/pro/billing', name: 'pro_billing', methods: ['GET'])]
    public function index(): Response
    {
        $company = $this->billingCompany();
        $subscription = $this->subscriptions->findCurrent($company) ?? $this->subscriptions->findPending($company);
        $config = $company->getConfig();

        return $this->render('pro/billing/index.html.twig', [
            'context' => $this->proContext(),
            'company' => ['name' => $company->getName(), 'establishmentsCount' => $company->getEstablishments()->count()],
            'subscription' => $subscription !== null ? $this->subscriptionPresenter->summary($subscription) : null,
            'alert' => $this->subscriptionPresenter->alert($company, $subscription),
            'paymentMethods' => $this->paymentPresenter->methods($this->methods->findForCompany($company)),
            'invoices' => $this->invoicePresenter->rows($this->invoices->findForCompany($company)),
            'billing' => [
                'billingName' => $this->billingInfo->billingName($company),
                'billingEmail' => $config?->getBillingEmail(),
                'vatNumber' => $company->getVatNumber(),
                'addressLine1' => $config?->getAddressLine1(),
                'addressLine2' => $config?->getAddressLine2(),
                'postalCode' => $config?->getPostalCode(),
                'locality' => $config?->getLocality(),
            ],
            'gatewayKey' => $this->gateway->getPublicKey(),
        ]);
    }

    #[Csrf('pro')]
    #[Route('/pro/billing/info', name: 'pro_billing_info', methods: ['POST'])]
    public function info(#[MapRequestPayload(validate: false)] BillingInfoInput $input): RedirectResponse
    {
        $company = $this->billingCompany();
        $input->vatNumber = $input->vatNumber !== null && trim($input->vatNumber) !== '' ? strtoupper(str_replace(' ', '', $input->vatNumber)) : null;
        $input->addressLine2 = $input->addressLine2 !== null && trim($input->addressLine2) !== '' ? $input->addressLine2 : null;

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->billingInfo->update($company, $input), 'Informations de facturation enregistrées.');
        }

        return $this->redirect($this->generateUrl('pro_billing') . '#informations');
    }

    #[Csrf('pro')]
    #[Route('/pro/billing/payment-methods/setup', name: 'pro_billing_method_setup', methods: ['POST'])]
    public function setup(#[MapRequestPayload(validate: false)] PaymentSetupInput $input): JsonResponse
    {
        $company = $this->billingCompany();

        try {
            $setup = $this->methodService->startSetup($company, PaymentMethodType::tryFrom($input->type) ?? PaymentMethodType::CARD);
        } catch (BusinessRuleException|PaymentGatewayException $exception) {
            return $this->json(['error' => $exception->getMessage()], 422);
        }

        return $this->json(['setup' => $setup->toArray(), 'gatewayKey' => $this->gateway->getPublicKey()]);
    }

    #[Csrf('pro')]
    #[Route('/pro/billing/payment-methods', name: 'pro_billing_method_add', methods: ['POST'])]
    public function addMethod(Request $request): RedirectResponse
    {
        $company = $this->billingCompany();
        $input = new PaymentMethodInput();
        $input->setupReference = trim($request->request->getString('setupReference')) ?: null;
        $input->makeDefault = $request->request->getBoolean('makeDefault');
        $input->mandateAccepted = $request->request->getBoolean('mandateAccepted');

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->methodService->add($company, $this->currentUser(), $input), 'Moyen de paiement enregistré.');
        }

        return $this->redirect($this->generateUrl('pro_billing') . '#moyens');
    }

    #[Csrf('pro')]
    #[Route('/pro/billing/payment-methods/{id}/default', name: 'pro_billing_method_default', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function defaultMethod(int $id): RedirectResponse
    {
        $company = $this->billingCompany();
        $method = $this->findMethod($company, $id);
        $this->attempt(fn () => $this->methodService->setDefault($company, $method), 'Moyen de paiement par défaut modifié.');

        return $this->redirect($this->generateUrl('pro_billing') . '#moyens');
    }

    #[Csrf('pro')]
    #[Route('/pro/billing/payment-methods/{id}/delete', name: 'pro_billing_method_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteMethod(int $id): RedirectResponse
    {
        $company = $this->billingCompany();
        $method = $this->findMethod($company, $id);
        $this->attempt(fn () => $this->methodService->remove($company, $method), 'Moyen de paiement supprimé.');

        return $this->redirect($this->generateUrl('pro_billing') . '#moyens');
    }

    #[Route('/pro/billing/invoices/{number}', name: 'pro_billing_invoice', methods: ['GET'], requirements: ['number' => InvoiceNumberHelper::PATTERN])]
    public function invoice(string $number): Response
    {
        $company = $this->billingCompany();

        return $this->render('pro/billing/invoice.html.twig', [
            'context' => $this->proContext(),
            'invoice' => $this->invoicePresenter->detail($this->findInvoice($company, $number), $this->billingInfo->billingEmail($company)),
            'paymentMethods' => array_values(array_filter($this->paymentPresenter->methods($this->methods->findForCompany($company)), static fn (array $method): bool => !$method['expired'])),
        ]);
    }

    #[Csrf('pro')]
    #[Route('/pro/billing/invoices/{number}/pay', name: 'pro_billing_invoice_pay', methods: ['POST'], requirements: ['number' => InvoiceNumberHelper::PATTERN])]
    public function pay(Request $request, string $number): RedirectResponse
    {
        $company = $this->billingCompany();
        $invoice = $this->findInvoice($company, $number);
        $methodId = $request->request->getInt('paymentMethodId');
        $method = $methodId > 0 ? $this->findMethod($company, $methodId) : null;
        $attempt = null;

        if ($method !== null && $request->request->getBoolean('makeDefault')) {
            $this->attempt(fn () => $this->methodService->setDefault($company, $method));
        }

        $paid = $this->attempt(function () use ($invoice, $method, &$attempt): void {
            $attempt = $this->payments->collect($invoice, $method, $this->currentUser(), false);
        });

        if (!$paid) {
            return $this->redirectToRoute('pro_billing_invoice', ['number' => $number]);
        }

        return $this->afterAttempt($attempt);
    }

    #[Route('/pro/billing/payments/{id}', name: 'pro_billing_payment', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function payment(int $id): Response
    {
        $company = $this->billingCompany();
        $payment = $this->paymentRepository->findOneForCompany($company, $id) ?? throw $this->createNotFoundException('Paiement introuvable.');
        $actions = $this->session->get(self::PAYMENT_ACTIONS);
        $secret = is_array($actions) && $payment->getStatus() === PaymentStatus::REQUIRES_ACTION ? ($actions[$id] ?? null) : null;

        return $this->render('pro/billing/payment.html.twig', [
            'context' => $this->proContext(),
            'payment' => $this->paymentPresenter->result($payment, $this->subscriptions->findCurrent($company), $this->methods->findForCompany($company)),
            'billingEmail' => $this->billingInfo->billingEmail($company),
            'action' => is_string($secret) ? ['clientSecret' => $secret, 'gatewayKey' => $this->gateway->getPublicKey()] : null,
        ]);
    }

    #[Csrf('pro')]
    #[Route('/pro/billing/payments/{id}/confirm', name: 'pro_billing_payment_confirm', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function confirm(int $id): RedirectResponse
    {
        $company = $this->billingCompany();
        $payment = $this->paymentRepository->findOneForCompany($company, $id) ?? throw $this->createNotFoundException('Paiement introuvable.');
        $this->attempt(fn () => $this->payments->confirm($payment));
        $actions = $this->session->get(self::PAYMENT_ACTIONS);

        if (is_array($actions) && $payment->getStatus() !== PaymentStatus::REQUIRES_ACTION) {
            unset($actions[$id]);
            $this->session->set(self::PAYMENT_ACTIONS, $actions);
        }

        return $this->redirectToRoute('pro_billing_payment', ['id' => $id]);
    }

    private function afterAttempt(?PaymentAttempt $attempt): RedirectResponse
    {
        if ($attempt === null) {
            $this->addFlash('success', 'Facture réglée.');

            return $this->redirectToRoute('pro_billing');
        }

        $payment = $attempt->getPayment();

        if ($attempt->requiresAction() && $attempt->getClientSecret() !== null) {
            $actions = $this->session->get(self::PAYMENT_ACTIONS);
            $actions = is_array($actions) ? $actions : [];
            $actions[$payment->getId()] = $attempt->getClientSecret();
            $this->session->set(self::PAYMENT_ACTIONS, $actions);
        }

        return $this->redirectToRoute('pro_billing_payment', ['id' => $payment->getId()]);
    }

    private function billingCompany(): Company
    {
        $company = $this->currentCompany();
        $this->requirePermission(Permission::COMPANY_BILLING, $company);

        return $company;
    }

    private function findMethod(Company $company, int $id): CompanyPaymentMethod
    {
        return $this->methods->findOneForCompany($company, $id) ?? throw $this->createNotFoundException('Moyen de paiement introuvable.');
    }

    private function findInvoice(Company $company, string $number): CompanyInvoice
    {
        return $this->invoices->findOneForCompany($company, $number) ?? throw $this->createNotFoundException('Facture introuvable.');
    }
}