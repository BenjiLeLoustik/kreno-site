<?php

namespace App\Company\Service;

use App\Company\Enum\CompanyStatus;
use App\Company\Dto\EstablishmentInput;
use App\Company\Event\EstablishmentSubmittedEvent;
use App\Company\Helper\SiretHelper;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentMember;
use App\Establishment\Enum\EstablishmentRole;
use App\Establishment\Enum\EstablishmentStatus;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Repository\Company\CompanyMemberRepository;
use App\Repository\Establishment\EstablishmentMemberRepository;
use App\Shared\Exception\BusinessRuleException;
use App\Website\Provider\WebsiteProvider;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class EstablishmentRegistrationService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private CityRepository $cities,
        private CategoryRepository $categories,
        private CompanyEstablishmentRepository $establishments,
        private CompanyMemberRepository $members,
        private EstablishmentMemberRepository $establishmentMembers,
        private ProSignupService $signup,
        private WebsiteProvider $website,
        private EventManagerInterface $events,
    ) {
    }

    public function create(Company $company, EstablishmentInput $input): CompanyEstablishment
    {
        if ($input->name === null || trim($input->name) === '') {
            throw new BusinessRuleException('Le nom de l\'établissement est obligatoire.');
        }

        $plan = null;

        foreach ($company->getSubscriptions() as $subscription) {
            $plan = $subscription->getPlan();
            break;
        }

        if ($plan !== null && $plan->getMaxEstablishments() !== null && $company->getEstablishments()->count() >= $plan->getMaxEstablishments()) {
            throw new BusinessRuleException('Votre formule {plan} est limitée à {max} établissement(s). Passez à une formule supérieure pour en ajouter.', ['plan' => $plan->getName(), 'max' => $plan->getMaxEstablishments()]);
        }

        $establishment = (new CompanyEstablishment())->setName(trim($input->name))->setStatus(EstablishmentStatus::DRAFT);
        $this->apply($establishment, $input);

        if ($establishment->getCity() === null) {
            throw new BusinessRuleException('Choisissez la ville de l\'établissement.');
        }

        $company->addEstablishment($establishment);
        $this->orm->persist($establishment);

        if ($input->managerMemberId !== null) {
            $this->assignManager($company, $establishment, $input->managerMemberId);
        }

        if ($company->getStatus() === CompanyStatus::ACTIVE) {
            $establishment->setStatus(EstablishmentStatus::PENDING_REVIEW);
        }

        $this->orm->flush();

        if ($establishment->getStatus() === EstablishmentStatus::PENDING_REVIEW) {
            $this->events->dispatch(new EstablishmentSubmittedEvent($establishment));
        }

        return $establishment;
    }

    public function update(CompanyEstablishment $establishment, EstablishmentInput $input): CompanyEstablishment
    {
        if ($input->name !== null && trim($input->name) !== '') {
            $establishment->setName(trim($input->name));
        }

        $this->apply($establishment, $input);

        if ($input->managerMemberId !== null && $establishment->getCompany() !== null) {
            $this->assignManager($establishment->getCompany(), $establishment, $input->managerMemberId);
        }

        $this->orm->flush();

        return $establishment;
    }

    public function submit(CompanyEstablishment $establishment): CompanyEstablishment
    {
        if (!in_array($establishment->getStatus(), [EstablishmentStatus::DRAFT, EstablishmentStatus::REJECTED], true)) {
            return $establishment;
        }

        foreach (['getSiret' => 'le SIRET', 'getCity' => 'la ville', 'getCategory' => 'la catégorie', 'getAddressLine1' => 'l\'adresse'] as $getter => $label) {
            if ($establishment->{$getter}() === null) {
                throw new BusinessRuleException('Il manque {field} de l\'établissement.', ['field' => $label]);
            }
        }

        $establishment->setStatus(EstablishmentStatus::PENDING_REVIEW)->setRejectionReason(null);
        $this->orm->flush();

        $this->events->dispatch(new EstablishmentSubmittedEvent($establishment));

        return $establishment;
    }

    private function apply(CompanyEstablishment $establishment, EstablishmentInput $input): void
    {
        if ($input->siret !== null && SiretHelper::normalize($input->siret) !== $establishment->getSiret()) {
            if ($establishment->getStatus() === EstablishmentStatus::ONLINE) {
                throw new BusinessRuleException('Le SIRET d\'un établissement en ligne ne peut pas être modifié. Contactez pro@kreno.fr.');
            }

            $registry = $this->signup->lookupSiret($input->siret);
            $establishment->setSiret($registry->siret)->setApeCode($registry->apeCode);
        }

        if ($input->citySlug !== null) {
            $city = $this->cities->findOneBySlug($input->citySlug);

            if ($city === null || !in_array($city, $this->cities->findAvailableForPros(), true)) {
                throw new BusinessRuleException('Kréno n\'est pas encore disponible dans cette ville.');
            }

            if ($establishment->getStatus() === EstablishmentStatus::ONLINE && $establishment->getCity() !== $city) {
                throw new BusinessRuleException('Un établissement en ligne ne peut pas changer de ville.');
            }

            $establishment->setCity($city);
        }

        if ($input->categorySlug !== null) {
            $establishment->setCategory($this->categories->findOneVisibleBySlug($input->categorySlug) ?? throw new BusinessRuleException('Catégorie inconnue.'));
        }

        foreach (['addressLine1', 'addressLine2', 'postalCode', 'locality', 'phone', 'website', 'description'] as $field) {
            if ($input->{$field} !== null) {
                $establishment->{'set' . ucfirst($field)}(trim($input->{$field}) ?: null);
            }
        }

        if ($input->email !== null) {
            $establishment->setEmail(mb_strtolower(trim($input->email)) ?: null);
        }

        if ($input->latitude !== null && $input->longitude !== null) {
            $establishment->setLatitude(number_format($input->latitude, 7, '.', ''))->setLongitude(number_format($input->longitude, 7, '.', ''));
        }

        if ($input->defaultDiscount !== null) {
            $rule = $this->website->getRule();

            if ($input->defaultDiscount < $rule->getMinDiscount() || $input->defaultDiscount > $rule->getMaxDiscount()) {
                throw new BusinessRuleException('La remise doit être comprise entre {min} et {max} %.', ['min' => $rule->getMinDiscount(), 'max' => $rule->getMaxDiscount()]);
            }

            $establishment->setDefaultDiscount($input->defaultDiscount);
        }
    }

    private function assignManager(Company $company, CompanyEstablishment $establishment, int $memberId): void
    {
        $member = $this->members->findOneInCompany($company, $memberId) ?? throw new BusinessRuleException('Ce membre ne fait pas partie de l\'entreprise.');

        if ($member->getRole() !== null) {
            return;
        }

        $role = $establishment->getId() !== null ? $this->establishmentMembers->findOneByEstablishmentAndMember($establishment, $member) : null;

        if ($role === null) {
            $role = new EstablishmentMember();
            $member->addEstablishmentRole($role);
            $establishment->addMember($role);
            $this->orm->persist($role);
        }

        $role->setRole(EstablishmentRole::MANAGER)->setCanManageBookings(true);
    }
}