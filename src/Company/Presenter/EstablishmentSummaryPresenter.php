<?php

namespace App\Company\Presenter;

use App\Company\Helper\SiretHelper;
use App\Entity\Company\CompanyEstablishment;
use App\Establishment\Enum\EstablishmentRole;
use App\Shared\Helper\DateHelper;
use App\User\Helper\UserNameHelper;

class EstablishmentSummaryPresenter
{
    public function summary(CompanyEstablishment $establishment): array
    {
        return [
            'id' => $establishment->getId(),
            'name' => $establishment->getName(),
            'slug' => $establishment->getSlug(),
            'status' => $establishment->getStatus()->value,
            'statusLabel' => $establishment->getStatus()->label(),
            'city' => $establishment->getCity() === null ? null : ['name' => $establishment->getCity()->getName(), 'slug' => $establishment->getCity()->getSlug()],
            'category' => $establishment->getCategory() === null ? null : ['name' => $establishment->getCategory()->getName(), 'slug' => $establishment->getCategory()->getSlug()],
            'address' => trim(sprintf('%s, %s %s', $establishment->getAddressLine1(), $establishment->getPostalCode(), $establishment->getLocality()), ', '),
        ];
    }

    public function forCompany(CompanyEstablishment $establishment): array
    {
        $manager = null;

        foreach ($establishment->getMembers() as $member) {
            if ($member->getRole() === EstablishmentRole::MANAGER && $member->getMember()?->getUser() !== null) {
                $manager = UserNameHelper::fullName($member->getMember()->getUser());
                break;
            }
        }

        return $this->summary($establishment) + [
                'manager' => $manager,
                'membersCount' => $establishment->getMembers()->count(),
                'siret' => $establishment->getSiret(),
            ];
    }

    public function forModeration(CompanyEstablishment $establishment): array
    {
        $company = $establishment->getCompany();
        $owner = null;

        foreach ($company?->getMembers() ?? [] as $member) {
            if ($member->getRole()?->value === 'owner' && $member->getUser() !== null) {
                $owner = ['name' => UserNameHelper::fullName($member->getUser()), 'email' => $member->getUser()->getEmail(), 'phone' => $member->getUser()->getPhone()];
            }
        }

        $plan = null;

        foreach ($company?->getSubscriptions() ?? [] as $subscription) {
            $plan = $subscription->getPlan()?->getName();
            break;
        }

        return $this->summary($establishment) + [
                'siret' => $establishment->getSiret(),
                'siretLabel' => $establishment->getSiret() !== null ? SiretHelper::format($establishment->getSiret()) : null,
                'apeCode' => $establishment->getApeCode(),
                'description' => $establishment->getDescription(),
                'imagesCount' => $establishment->getImages()->count(),
                'servicesCount' => $establishment->getServices()->count(),
                'company' => $company === null ? null : ['id' => $company->getId(), 'name' => $company->getName(), 'legalName' => $company->getLegalName(), 'siren' => $company->getSiren(), 'status' => $company->getStatus()->value],
                'owner' => $owner,
                'plan' => $plan,
                'rejectionReason' => $establishment->getRejectionReason(),
                'submittedAt' => DateHelper::iso($establishment->getUpdatedAt()),
                'verifiedAt' => DateHelper::iso($establishment->getVerifiedAt()),
                'checks' => [
                    'siretMatchesCompany' => $establishment->getSiret() !== null && $company?->getSiren() !== null && str_starts_with($establishment->getSiret(), $company->getSiren()),
                    'hasAddress' => $establishment->getAddressLine1() !== null,
                    'hasPhotosAndDescription' => $establishment->getImages()->count() > 0 && $establishment->getDescription() !== null,
                ],
            ];
    }
}