<?php

namespace App\Company\Service;

use App\Company\Dto\CompanyInput;
use App\Company\Helper\SiretHelper;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyConfig;
use App\Repository\Company\CompanyRepository;
use App\Shared\Exception\BusinessRuleException;
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

        if ($input->name !== null && trim($input->name) !== '') {
            $company->setName(trim($input->name));
        }

        if ($input->legalName !== null) {
            $company->setLegalName(trim($input->legalName) ?: null);
        }

        if ($input->siren !== null) {
            $siren = SiretHelper::normalize($input->siren);

            if (!SiretHelper::isValidSiren($siren)) {
                throw new BusinessRuleException('Ce numéro SIREN n\'est pas valide.');
            }

            if ($this->companies->sirenExists($siren, $company)) {
                throw new BusinessRuleException('Une autre entreprise utilise déjà ce SIREN.');
            }

            $company->setSiren($siren);
        }

        if ($input->vatNumber !== null) {
            $company->setVatNumber(strtoupper(str_replace(' ', '', $input->vatNumber)) ?: null);
        }

        if ($input->addressLine1 !== null) {
            $company->setAddressLine1(trim($input->addressLine1) ?: null);
        }

        if ($input->postalCode !== null) {
            $company->setPostalCode($input->postalCode ?: null);
        }

        if ($input->locality !== null) {
            $company->setLocality(trim($input->locality) ?: null);
        }

        if ($input->defaultDiscount !== null) {
            $rule = $this->website->getRule();

            if ($input->defaultDiscount < $rule->getMinDiscount() || $input->defaultDiscount > $rule->getMaxDiscount()) {
                throw new BusinessRuleException('La remise par défaut doit être comprise entre {min} et {max} %.', ['min' => $rule->getMinDiscount(), 'max' => $rule->getMaxDiscount()]);
            }

            $config->setDefaultDiscount($input->defaultDiscount);
        }

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

        $this->orm->persist($config);
        $this->orm->flush();

        return $company;
    }
}