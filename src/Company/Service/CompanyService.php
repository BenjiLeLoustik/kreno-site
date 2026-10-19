<?php

namespace App\Company\Service;

use App\Company\Dto\CompanyInput;
use App\Company\Exception\DefaultDiscountOutOfRangeException;
use App\Company\Exception\InvalidSirenException;
use App\Company\Exception\SirenAlreadyUsedException;
use App\Company\Helper\SiretHelper;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyConfig;
use App\Repository\Company\CompanyRepository;
use App\Website\Provider\WebsiteProvider;
use NeoPHP\Package\Orm\OrmManagerInterface;

class CompanyService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private CompanyRepository $companies,
        private WebsiteProvider $website,
    ) {
    }

    public function update(Company $company, CompanyInput $input): Company
    {
        $config = $company->getConfig() ?? (new CompanyConfig())->setCompany($company);

        $this->applyIdentity($company, $input);
        $this->applySiren($company, $input->siren);
        $this->applyAddress($company, $input);
        $this->applyDiscount($config, $input->defaultDiscount);
        $this->applyConfig($config, $input);

        $this->orm->persist($config);
        $this->orm->flush();

        return $company;
    }

    private function applyIdentity(Company $company, CompanyInput $input): void
    {
        if ($input->name !== null && trim($input->name) !== '') {
            $company->setName(trim($input->name));
        }

        if ($input->legalName !== null) {
            $company->setLegalName(trim($input->legalName) ?: null);
        }

        if ($input->vatNumber !== null) {
            $company->setVatNumber(
                strtoupper(str_replace(' ', '', $input->vatNumber)) ?: null
            );
        }
    }

    private function applySiren(Company $company, ?string $siren): void
    {
        if ($siren === null) {
            return;
        }

        $siren = SiretHelper::normalize($siren);

        if (!SiretHelper::isValidSiren($siren)) {
            throw new InvalidSirenException();
        }

        if ($this->companies->sirenExists($siren, $company)) {
            throw new SirenAlreadyUsedException();
        }

        $company->setSiren($siren);
    }

    private function applyAddress(Company $company, CompanyInput $input): void
    {
        if ($input->addressLine1 !== null) {
            $company->setAddressLine1(trim($input->addressLine1) ?: null);
        }

        if ($input->postalCode !== null) {
            $company->setPostalCode($input->postalCode ?: null);
        }

        if ($input->locality !== null) {
            $company->setLocality(trim($input->locality) ?: null);
        }
    }

    private function applyDiscount(CompanyConfig $config, ?int $discount): void
    {
        if ($discount === null) {
            return;
        }

        $rule = $this->website->getRule();

        if ($discount < $rule->getMinDiscount() || $discount > $rule->getMaxDiscount()) {
            throw new DefaultDiscountOutOfRangeException($rule->getMinDiscount(), $rule->getMaxDiscount());
        }

        $config->setDefaultDiscount($discount);
    }

    private function applyConfig(CompanyConfig $config, CompanyInput $input): void
    {
        if ($input->billingName !== null) {
            $config->setBillingName(trim($input->billingName) ?: null);
        }

        if ($input->billingEmail !== null) {
            $config->setBillingEmail(mb_strtolower(trim($input->billingEmail)) ?: null);
        }

        if ($input->bookingEmailNotifications !== null) {
            $config->setBookingEmailNotifications($input->bookingEmailNotifications);
        }

        if ($input->monthlyTips !== null) {
            $config->setMonthlyTips($input->monthlyTips);
        }
    }
}