<?php

namespace App\Billing\Service;

use App\Billing\Dto\PaymentMethodInput;
use App\Billing\Dto\SignupPaymentInput;
use App\Billing\Exception\BillingInfoRequiredException;
use App\Billing\Exception\EstablishmentRequiredException;
use App\Billing\Exception\PlanRequiredException;
use App\Company\Enum\CompanyStatus;
use App\Company\Exception\MemberWithoutCompanyException;
use App\Company\Exception\MemberWithoutUserException;
use App\Company\Service\EstablishmentRegistrationService;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Entity\Company\CompanySubscription;
use App\Repository\Company\CompanySubscriptionRepository;
use NeoPHP\Package\Orm\OrmManagerInterface;

class SignupBillingService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private CompanySubscriptionRepository $subscriptions,
        private BillingInfoService $billingInfo,
        private PaymentMethodService $methods,
        private SubscriptionService $subscriptionService,
        private EstablishmentRegistrationService $registration,
    ) {
    }

    public function complete(CompanyMember $member, SignupPaymentInput $input): CompanySubscription
    {
        $company = $member->getCompany() ?? throw new MemberWithoutCompanyException();
        $user = $member->getUser() ?? throw new MemberWithoutUserException();
        $subscription = $this->subscriptions->findPending($company) ?? throw new PlanRequiredException();
        $establishment = $company->getEstablishments()->first();

        if (!$establishment instanceof CompanyEstablishment) {
            throw new EstablishmentRequiredException();
        }

        if ($input->billing === null) {
            throw new BillingInfoRequiredException();
        }

        $this->billingInfo->update($company, $input->billing);

        if ($input->promoCode !== null && trim($input->promoCode) !== '') {
            $this->subscriptionService->applyPromo($subscription, $input->promoCode);
        }

        $this->methods->add($company, $user, $this->paymentMethodInput($input));
        $this->registration->submit($establishment);

        if ($company->getStatus() === CompanyStatus::ONBOARDING) {
            $company->setStatus(CompanyStatus::PENDING_REVIEW);
        }

        $this->orm->flush();

        return $subscription;
    }

    private function paymentMethodInput(SignupPaymentInput $input): PaymentMethodInput
    {
        $method = new PaymentMethodInput();
        $method->setupReference = $input->setupReference;
        $method->makeDefault = true;
        $method->mandateAccepted = $input->mandateAccepted;

        return $method;
    }
}