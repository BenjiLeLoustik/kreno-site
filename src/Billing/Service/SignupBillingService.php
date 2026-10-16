<?php

namespace App\Billing\Service;

use App\Billing\Dto\PaymentMethodInput;
use App\Billing\Dto\SignupPaymentInput;
use App\Company\Enum\CompanyStatus;
use App\Company\Service\EstablishmentRegistrationService;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Entity\Company\CompanySubscription;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Shared\Exception\BusinessRuleException;
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
        $company = $member->getCompany() ?? throw new BusinessRuleException('Aucune entreprise.');
        $user = $member->getUser() ?? throw new BusinessRuleException('Aucun utilisateur.');
        $subscription = $this->subscriptions->findPending($company) ?? throw new BusinessRuleException('Choisissez d\'abord votre formule.');
        $establishment = $company->getEstablishments()->first();

        if (!$establishment instanceof CompanyEstablishment) {
            throw new BusinessRuleException('Renseignez d\'abord votre établissement.');
        }

        if ($input->billing === null) {
            throw new BusinessRuleException('Indiquez vos informations de facturation.');
        }

        $this->billingInfo->update($company, $input->billing);

        if ($input->promoCode !== null && trim($input->promoCode) !== '') {
            $this->subscriptionService->applyPromo($subscription, $input->promoCode);
        }

        $method = new PaymentMethodInput();
        $method->setupReference = $input->setupReference;
        $method->makeDefault = true;
        $method->mandateAccepted = $input->mandateAccepted;
        $this->methods->add($company, $user, $method);

        $this->registration->submit($establishment);

        if ($company->getStatus() === CompanyStatus::ONBOARDING) {
            $company->setStatus(CompanyStatus::PENDING_REVIEW);
        }

        $this->orm->flush();

        return $subscription;
    }
}