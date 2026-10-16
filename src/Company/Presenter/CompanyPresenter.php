<?php

namespace App\Company\Presenter;

use App\Entity\Company\Company;
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
        $config = $company->getConfig();

        return $this->summary($company) + [
                'siren' => $company->getSiren(),
                'sirenLabel' => $company->getSiren() !== null ? trim(chunk_split($company->getSiren(), 3, ' ')) : null,
                'vatNumber' => $company->getVatNumber(),
                'address' => [
                    'line1' => $company->getAddressLine1(),
                    'postalCode' => $company->getPostalCode(),
                    'locality' => $company->getLocality(),
                ],
                'verifiedAt' => DateHelper::iso($company->getVerifiedAt()),
                'config' => $config === null ? null : [
                    'defaultDiscount' => $config->getDefaultDiscount(),
                    'billingName' => $config->getBillingName(),
                    'billingEmail' => $config->getBillingEmail(),
                    'bookingEmailNotifications' => $config->isBookingEmailNotifications(),
                    'monthlyTips' => $config->isMonthlyTips(),
                ],
                'establishmentsCount' => $company->getEstablishments()->count(),
                'membersCount' => $company->getMembers()->count(),
            ];
    }
}