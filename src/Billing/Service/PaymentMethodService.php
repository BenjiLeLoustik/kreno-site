<?php

namespace App\Billing\Service;

use App\Billing\Dto\PaymentMethodInput;
use App\Billing\Enum\PaymentMethodType;
use App\Billing\Event\PaymentMethodAddedEvent;
use App\Billing\Exception\LastPaymentMethodException;
use App\Billing\Exception\SepaMandateRequiredException;
use App\Billing\Gateway\PaymentGatewayInterface;
use App\Billing\Model\GatewayPaymentMethod;
use App\Billing\Model\GatewaySetup;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyPaymentMethod;
use App\Entity\User\User;
use App\Repository\Company\CompanyPaymentMethodRepository;
use App\Repository\Company\CompanySubscriptionRepository;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class PaymentMethodService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private EventManagerInterface $events,
        private PaymentGatewayInterface $gateway,
        private CompanyPaymentMethodRepository $methods,
        private CompanySubscriptionRepository $subscriptions,
    ) {
    }

    public function startSetup(Company $company, PaymentMethodType $type): GatewaySetup
    {
        return $this->gateway->createSetup($company, $type, $this->methods->findDefault($company));
    }

    public function add(Company $company, User $user, PaymentMethodInput $input): CompanyPaymentMethod
    {
        $details = $this->gateway->completeSetup((string) $input->setupReference);

        if ($details->getType() === PaymentMethodType::SEPA_DEBIT && !$input->mandateAccepted) {
            throw new SepaMandateRequiredException();
        }

        $existing = $this->methods->findOneByProviderReference($details->getReference());

        if ($existing !== null) {
            return $existing;
        }

        $first = $this->methods->findForCompany($company) === [];
        $method = $this->createMethod($user, $details);

        $company->addPaymentMethod($method);
        $this->orm->persist($method);

        if ($first || $input->makeDefault) {
            $this->makeDefault($company, $method, false);
        }

        $this->orm->flush();
        $this->events->dispatch(new PaymentMethodAddedEvent($method));

        return $method;
    }

    public function setDefault(Company $company, CompanyPaymentMethod $method): CompanyPaymentMethod
    {
        $this->makeDefault($company, $method, true);

        return $method;
    }

    public function remove(Company $company, CompanyPaymentMethod $method): void
    {
        $others = $this->otherMethods($company, $method);

        if ($others === [] && $this->subscriptions->findCurrent($company) !== null) {
            throw new LastPaymentMethodException();
        }

        $this->gateway->detach($method);
        $company->removePaymentMethod($method);
        $this->orm->remove($method);

        if ($method->isDefaultMethod() && $others !== []) {
            $others[0]->setDefaultMethod(true);
        }

        $this->orm->flush();
    }

    private function createMethod(User $user, GatewayPaymentMethod $details): CompanyPaymentMethod
    {
        $method = new CompanyPaymentMethod();
        $method->setAddedBy($user);
        $method->setType($details->getType());
        $method->setProviderReference($details->getReference());
        $method->setBrand($details->getBrand());
        $method->setLast4($details->getLast4());
        $method->setExpMonth($details->getExpMonth());
        $method->setExpYear($details->getExpYear());
        $method->setHolderName($details->getHolderName());
        $method->setMandateReference($details->getMandateReference());

        if ($details->getType() === PaymentMethodType::SEPA_DEBIT) {
            $method->setMandateAcceptedAt(new DateTimeImmutable());
        }

        return $method;
    }

    private function otherMethods(Company $company, CompanyPaymentMethod $method): array
    {
        $others = [];

        foreach ($this->methods->findForCompany($company) as $other) {
            if ($other !== $method) {
                $others[] = $other;
            }
        }

        return $others;
    }

    private function makeDefault(Company $company, CompanyPaymentMethod $method, bool $flush): void
    {
        foreach ($this->methods->findForCompany($company) as $other) {
            $other->setDefaultMethod($other === $method);
        }

        $method->setDefaultMethod(true);

        if ($flush) {
            $this->orm->flush();
        }
    }
}