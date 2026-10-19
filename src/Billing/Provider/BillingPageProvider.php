<?php

namespace App\Billing\Provider;

use App\Billing\Exception\InvoiceNotFoundException;
use App\Billing\Exception\PaymentMethodNotFoundException;
use App\Billing\Exception\PaymentNotFoundException;
use App\Billing\Gateway\PaymentGatewayInterface;
use App\Billing\Presenter\InvoicePresenter;
use App\Billing\Presenter\PaymentPresenter;
use App\Billing\Presenter\SubscriptionPresenter;
use App\Billing\Service\BillingInfoService;
use App\Billing\Service\PaymentActionStore;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyInvoice;
use App\Entity\Company\CompanyPayment;
use App\Entity\Company\CompanyPaymentMethod;
use App\Entity\Company\CompanySubscription;
use App\Repository\Company\CompanyInvoiceRepository;
use App\Repository\Company\CompanyPaymentMethodRepository;
use App\Repository\Company\CompanyPaymentRepository;
use App\Repository\Company\CompanySubscriptionRepository;

class BillingPageProvider
{
    public function __construct(
        private PaymentGatewayInterface $gateway,
        private BillingInfoService $billingInfo,
        private PaymentActionStore $actions,
        private CompanySubscriptionRepository $subscriptions,
        private CompanyInvoiceRepository $invoices,
        private CompanyPaymentMethodRepository $methods,
        private CompanyPaymentRepository $payments,
        private SubscriptionPresenter $subscriptionPresenter,
        private InvoicePresenter $invoicePresenter,
        private PaymentPresenter $paymentPresenter,
    ) {
    }

    public function index(Company $company): array
    {
        $subscription = $this->subscriptions->findCurrent($company) ?? $this->subscriptions->findPending($company);

        return [
            'company' => [
                'name' => $company->getName(),
                'establishmentsCount' => $company->getEstablishments()->count(),
            ],
            'subscription' => $this->subscriptionSummary($subscription),
            'alert' => $this->subscriptionPresenter->alert($company, $subscription),
            'paymentMethods' => $this->paymentPresenter->methods($this->methods->findForCompany($company)),
            'invoices' => $this->invoicePresenter->rows($this->invoices->findForCompany($company)),
            'billing' => $this->billing($company),
            'gatewayKey' => $this->gateway->getPublicKey(),
        ];
    }

    public function invoicePage(Company $company, string $number): array
    {
        $invoice = $this->invoice($company, $number);

        return [
            'invoice' => $this->invoicePresenter->detail($invoice, $this->billingInfo->billingEmail($company)),
            'paymentMethods' => $this->usableMethods($company),
        ];
    }

    public function paymentPage(Company $company, int $id): array
    {
        $payment = $this->payment($company, $id);
        $current = $this->subscriptions->findCurrent($company);

        return [
            'payment' => $this->paymentPresenter->result($payment, $current, $this->methods->findForCompany($company)),
            'billingEmail' => $this->billingInfo->billingEmail($company),
            'action' => $this->action($payment),
        ];
    }

    public function usableMethods(Company $company): array
    {
        $methods = [];

        foreach ($this->paymentPresenter->methods($this->methods->findForCompany($company)) as $method) {
            if (!$method['expired']) {
                $methods[] = $method;
            }
        }

        return $methods;
    }

    public function method(Company $company, int $id): CompanyPaymentMethod
    {
        $method = $this->methods->findOneForCompany($company, $id);

        if ($method === null) {
            throw new PaymentMethodNotFoundException();
        }

        return $method;
    }

    public function chosenMethod(Company $company, int $id): ?CompanyPaymentMethod
    {
        if ($id <= 0) {
            return null;
        }

        return $this->method($company, $id);
    }

    public function invoice(Company $company, string $number): CompanyInvoice
    {
        $invoice = $this->invoices->findOneForCompany($company, $number);

        if ($invoice === null) {
            throw new InvoiceNotFoundException();
        }

        return $invoice;
    }

    public function payment(Company $company, int $id): CompanyPayment
    {
        $payment = $this->payments->findOneForCompany($company, $id);

        if ($payment === null) {
            throw new PaymentNotFoundException();
        }

        return $payment;
    }

    private function subscriptionSummary(?CompanySubscription $subscription): ?array
    {
        if ($subscription === null) {
            return null;
        }

        return $this->subscriptionPresenter->summary($subscription);
    }

    private function billing(Company $company): array
    {
        $config = $company->getConfig();

        return [
            'billingName' => $this->billingInfo->billingName($company),
            'billingEmail' => $config?->getBillingEmail(),
            'vatNumber' => $company->getVatNumber(),
            'addressLine1' => $config?->getAddressLine1(),
            'addressLine2' => $config?->getAddressLine2(),
            'postalCode' => $config?->getPostalCode(),
            'locality' => $config?->getLocality(),
        ];
    }

    private function action(CompanyPayment $payment): ?array
    {
        $secret = $this->actions->secret($payment);

        if ($secret === null) {
            return null;
        }

        return [
            'clientSecret' => $secret,
            'gatewayKey' => $this->gateway->getPublicKey()
        ];
    }
}