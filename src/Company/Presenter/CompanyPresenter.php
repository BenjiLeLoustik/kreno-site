<?php

namespace App\Company\Presenter;

use App\Entity\Company\Company;
use App\Entity\Company\CompanyConfig;
use App\Shared\Helper\DateHelper;

class CompanyPresenter
{
    public function summary(Company $company): array
    {
        return [
            'id' => $company->getId(),
            'name' => $company->getName(),
            'legalName' => $company->getLegalName(),
            'status' => $company->getStatus()->value,
            'statusLabel' => $company->getStatus()->label(),
            'kind' => $company->getKind()->value,
        ];
    }

    public function detail(Company $company): array
    {
        return $this->summary($company) + [
                'siren' => $company->getSiren(),
                'sirenLabel' => $this->sirenLabel($company->getSiren()),
                'vatNumber' => $company->getVatNumber(),
                'address' => [
                    'line1' => $company->getAddressLine1(),
                    'postalCode' => $company->getPostalCode(),
                    'locality' => $company->getLocality(),
                ],
                'verifiedAt' => DateHelper::iso($company->getVerifiedAt()),
                'config' => $this->config($company->getConfig()),
                'establishmentsCount' => $company->getEstablishments()->count(),
                'membersCount' => $company->getMembers()->count(),
            ];
    }

    private function sirenLabel(?string $siren): ?string
    {
        if ($siren === null) {
            return null;
        }

        return trim(chunk_split($siren, 3, ' '));
    }

    private function config(?CompanyConfig $config): ?array
    {
        if ($config === null) {
            return null;
        }

        return [
            'defaultDiscount' => $config->getDefaultDiscount(),
            'billingName' => $config->getBillingName(),
            'billingEmail' => $config->getBillingEmail(),
            'bookingEmailNotifications' => $config->isBookingEmailNotifications(),
            'monthlyTips' => $config->isMonthlyTips(),
        ];
    }
}