<?php

namespace App\Company\Presenter;

use App\Company\Enum\CompanyRole;
use App\Company\Helper\SiretHelper;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyEstablishment;
use App\Establishment\Enum\EstablishmentRole;
use App\Shared\Helper\DateHelper;
use App\User\Helper\UserNameHelper;

class EstablishmentSummaryPresenter
{
    public function summary(CompanyEstablishment $establishment): array
    {
        $city = $establishment->getCity();
        $category = $establishment->getCategory();

        return [
            'id' => $establishment->getId(),
            'name' => $establishment->getName(),
            'slug' => $establishment->getSlug(),
            'status' => $establishment->getStatus()->value,
            'statusLabel' => $establishment->getStatus()->label(),
            'city' => $city === null ? null : ['name' => $city->getName(), 'slug' => $city->getSlug()],
            'category' => $category === null
                ? null
                : [
                    'name' => $category->getName(),
                    'slug' => $category->getSlug()
                ],
            'address' => $this->address($establishment),
        ];
    }

    public function forCompany(CompanyEstablishment $establishment): array
    {
        return $this->summary($establishment) + [
                'manager' => $this->manager($establishment),
                'membersCount' => $establishment->getMembers()->count(),
                'siret' => $establishment->getSiret(),
            ];
    }

    public function forModeration(CompanyEstablishment $establishment): array
    {
        $company = $establishment->getCompany();
        $siret = $establishment->getSiret();

        return $this->summary($establishment) + [
                'siret' => $siret,
                'siretLabel' => $this->siretLabel($siret),
                'apeCode' => $establishment->getApeCode(),
                'description' => $establishment->getDescription(),
                'imagesCount' => $establishment->getImages()->count(),
                'servicesCount' => $establishment->getServices()->count(),
                'company' => $this->company($company),
                'owner' => $this->owner($company),
                'plan' => $this->plan($company),
                'rejectionReason' => $establishment->getRejectionReason(),
                'submittedAt' => DateHelper::iso($establishment->getUpdatedAt()),
                'verifiedAt' => DateHelper::iso($establishment->getVerifiedAt()),
                'checks' => [
                    'siretMatchesCompany' => $this->siretMatchesCompany($siret, $company),
                    'hasAddress' => $establishment->getAddressLine1() !== null,
                    'hasPhotosAndDescription' => $this->hasPhotosAndDescription($establishment),
                ],
            ];
    }

    private function address(CompanyEstablishment $establishment): string
    {
        $address = sprintf(
            '%s, %s %s',
            $establishment->getAddressLine1(),
            $establishment->getPostalCode(),
            $establishment->getLocality(),
        );

        return trim($address, ', ');
    }

    private function manager(CompanyEstablishment $establishment): ?string
    {
        foreach ($establishment->getMembers() as $member) {
            $user = $member->getMember()?->getUser();

            if ($member->getRole() === EstablishmentRole::MANAGER && $user !== null) {
                return UserNameHelper::fullName($user);
            }
        }

        return null;
    }

    private function siretLabel(?string $siret): ?string
    {
        if ($siret === null) {
            return null;
        }

        return SiretHelper::format($siret);
    }

    private function company(?Company $company): ?array
    {
        if ($company === null) {
            return null;
        }

        return [
            'id' => $company->getId(),
            'name' => $company->getName(),
            'legalName' => $company->getLegalName(),
            'siren' => $company->getSiren(),
            'status' => $company->getStatus()->value,
        ];
    }

    private function owner(?Company $company): ?array
    {
        $owner = null;

        foreach ($company?->getMembers() ?? [] as $member) {
            $user = $member->getUser();

            if ($member->getRole() === CompanyRole::OWNER && $user !== null) {
                $owner = [
                    'name' => UserNameHelper::fullName($user),
                    'email' => $user->getEmail(),
                    'phone' => $user->getPhone(),
                ];
            }
        }

        return $owner;
    }

    private function plan(?Company $company): ?string
    {
        foreach ($company?->getSubscriptions() ?? [] as $subscription) {
            return $subscription->getPlan()?->getName();
        }

        return null;
    }

    private function siretMatchesCompany(?string $siret, ?Company $company): bool
    {
        $siren = $company?->getSiren();

        if ($siret === null || $siren === null) {
            return false;
        }

        return str_starts_with($siret, $siren);
    }

    private function hasPhotosAndDescription(CompanyEstablishment $establishment): bool
    {
        return $establishment->getImages()->count() > 0 && $establishment->getDescription() !== null;
    }
}