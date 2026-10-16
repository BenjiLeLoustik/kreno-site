<?php

namespace App\Billing\Service;

use App\Billing\Dto\BillingInfoInput;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyConfig;
use NeoPHP\Package\Orm\OrmManagerInterface;

class BillingInfoService
{
    public function __construct(
        private OrmManagerInterface $orm,
    ) {
    }

    public function update(Company $company, BillingInfoInput $input, bool $flush = true): Company
    {
        $config = $this->config($company);
        $vat = mb_strtoupper(str_replace(' ', '', trim((string) $input->vatNumber)));

        $config->setBillingName(trim((string) $input->billingName));
        $config->setBillingEmail(mb_strtolower(trim((string) $input->billingEmail)));
        $config->setAddressLine1(trim((string) $input->addressLine1));
        $config->setAddressLine2(trim((string) $input->addressLine2) ?: null);
        $config->setPostalCode(trim((string) $input->postalCode));
        $config->setLocality(trim((string) $input->locality));
        $company->setVatNumber($vat !== '' ? $vat : null);

        if ($flush) {
            $this->orm->flush();
        }

        return $company;
    }

    public function config(Company $company): CompanyConfig
    {
        $config = $company->getConfig();

        if ($config === null) {
            $config = new CompanyConfig();
            $company->setConfig($config);
            $this->orm->persist($config);
        }

        return $config;
    }

    public function billingName(Company $company): string
    {
        return $company->getConfig()?->getBillingName() ?? $company->getLegalName() ?? (string) $company->getName();
    }

    public function billingAddress(Company $company): ?string
    {
        $config = $company->getConfig();

        if ($config === null || $config->getAddressLine1() === null) {
            return null;
        }

        return implode("\n", array_filter([$config->getAddressLine1(), $config->getAddressLine2(), trim($config->getPostalCode() . ' ' . $config->getLocality())]));
    }

    public function billingEmail(Company $company): ?string
    {
        return $company->getConfig()?->getBillingEmail();
    }
}