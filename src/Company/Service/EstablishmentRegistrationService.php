<?php

namespace App\Company\Service;

use App\Company\Dto\EstablishmentInput;
use App\Company\Enum\CompanyStatus;
use App\Company\Enum\EstablishmentRequirement;
use App\Company\Event\EstablishmentSubmittedEvent;
use App\Company\Exception\CityLockedException;
use App\Company\Exception\CityUnavailableException;
use App\Company\Exception\CompanyMemberNotFoundException;
use App\Company\Exception\DiscountOutOfRangeException;
use App\Company\Exception\EstablishmentCityRequiredException;
use App\Company\Exception\EstablishmentLimitReachedException;
use App\Company\Exception\EstablishmentNameRequiredException;
use App\Company\Exception\MissingEstablishmentFieldException;
use App\Company\Exception\SiretLockedException;
use App\Company\Exception\UnknownCategoryException;
use App\Company\Helper\SiretHelper;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Entity\Establishment\EstablishmentMember;
use App\Entity\Website\WebsitePlan;
use App\Establishment\Enum\EstablishmentRole;
use App\Establishment\Enum\EstablishmentStatus;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Repository\Company\CompanyMemberRepository;
use App\Repository\Establishment\EstablishmentMemberRepository;
use App\Shared\Helper\CoordinateHelper;
use App\Website\Provider\WebsiteProvider;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;
use NeoPHP\Package\Translation\TranslationManagerInterface;

class EstablishmentRegistrationService
{
    public const TEXT_FIELDS = [
        'addressLine1',
        'addressLine2',
        'postalCode',
        'locality',
        'phone',
        'website',
        'description',
    ];

    public const SUBMITTABLE_STATUSES = [
        EstablishmentStatus::DRAFT,
        EstablishmentStatus::REJECTED
    ];

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
        private TranslationManagerInterface $translation,
    ) {
    }

    public function create(Company $company, EstablishmentInput $input): CompanyEstablishment
    {
        $name = $this->text($input->name) ?? throw new EstablishmentNameRequiredException();
        $this->assertWithinPlan($company);

        $establishment = (new CompanyEstablishment())->setName($name)->setStatus(EstablishmentStatus::DRAFT);
        $this->apply($establishment, $input);

        if ($establishment->getCity() === null) {
            throw new EstablishmentCityRequiredException();
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
        $name = $this->text($input->name);

        if ($name !== null) {
            $establishment->setName($name);
        }

        $this->apply($establishment, $input);

        $company = $establishment->getCompany();

        if ($input->managerMemberId !== null && $company !== null) {
            $this->assignManager($company, $establishment, $input->managerMemberId);
        }

        $this->orm->flush();

        return $establishment;
    }

    public function submit(CompanyEstablishment $establishment): CompanyEstablishment
    {
        if (!in_array($establishment->getStatus(), self::SUBMITTABLE_STATUSES, true)) {
            return $establishment;
        }

        foreach (EstablishmentRequirement::cases() as $requirement) {
            if ($requirement->isMissing($establishment)) {
                throw new MissingEstablishmentFieldException($requirement->label());
            }
        }

        $establishment->setStatus(EstablishmentStatus::PENDING_REVIEW)->setRejectionReason(null);
        $this->orm->flush();

        $this->events->dispatch(new EstablishmentSubmittedEvent($establishment));

        return $establishment;
    }

    private function assertWithinPlan(Company $company): void
    {
        $plan = $this->currentPlan($company);
        $max = $plan?->getMaxEstablishments();

        if ($max === null || $company->getEstablishments()->count() < $max) {
            return;
        }

        throw new EstablishmentLimitReachedException((string) $plan->getName(), $max);
    }

    private function currentPlan(Company $company): ?WebsitePlan
    {
        foreach ($company->getSubscriptions() as $subscription) {
            return $subscription->getPlan();
        }

        return null;
    }

    private function apply(CompanyEstablishment $establishment, EstablishmentInput $input): void
    {
        $this->applySiret($establishment, $input->siret);
        $this->applyCity($establishment, $input->citySlug);
        $this->applyCategory($establishment, $input->categorySlug);
        $this->applyTexts($establishment, $input);
        $this->applyCoordinates($establishment, $input->latitude, $input->longitude);
        $this->applyDiscount($establishment, $input->defaultDiscount);

        if ($input->email !== null) {
            $establishment->setEmail(mb_strtolower(trim($input->email)) ?: null);
        }
    }

    private function applySiret(CompanyEstablishment $establishment, ?string $siret): void
    {
        if ($siret === null || SiretHelper::normalize($siret) === $establishment->getSiret()) {
            return;
        }

        if ($establishment->getStatus() === EstablishmentStatus::ONLINE) {
            throw new SiretLockedException();
        }

        $registry = $this->signup->lookupSiret($siret);
        $establishment->setSiret($registry->siret)->setApeCode($registry->apeCode);
    }

    private function applyCity(CompanyEstablishment $establishment, ?string $slug): void
    {
        if ($slug === null) {
            return;
        }

        $city = $this->cities->findOneBySlug($slug);

        if ($city === null || !in_array($city, $this->cities->findAvailableForPros(), true)) {
            throw new CityUnavailableException();
        }

        if ($establishment->getStatus() === EstablishmentStatus::ONLINE && $establishment->getCity() !== $city) {
            throw new CityLockedException();
        }

        $establishment->setCity($city);
    }

    private function applyCategory(CompanyEstablishment $establishment, ?string $slug): void
    {
        if ($slug === null) {
            return;
        }

        $category = $this->categories->findOneVisibleBySlug(
            $slug,
            $this->translation->getLocale()
        ) ?? throw new UnknownCategoryException();
        $establishment->setCategory($category);
    }

    private function applyTexts(CompanyEstablishment $establishment, EstablishmentInput $input): void
    {
        foreach (self::TEXT_FIELDS as $field) {
            if ($input->{$field} !== null) {
                $establishment->{'set' . ucfirst($field)}(trim($input->{$field}) ?: null);
            }
        }
    }

    private function applyCoordinates(CompanyEstablishment $establishment, ?float $latitude, ?float $longitude): void
    {
        if ($latitude === null || $longitude === null) {
            return;
        }

        $establishment
            ->setLatitude(CoordinateHelper::format($latitude))
            ->setLongitude(CoordinateHelper::format($longitude));
    }

    private function applyDiscount(CompanyEstablishment $establishment, ?int $discount): void
    {
        if ($discount === null) {
            return;
        }

        $rule = $this->website->getRule();

        if ($discount < $rule->getMinDiscount() || $discount > $rule->getMaxDiscount()) {
            throw new DiscountOutOfRangeException($rule->getMinDiscount(), $rule->getMaxDiscount());
        }

        $establishment->setDefaultDiscount($discount);
    }

    private function assignManager(Company $company, CompanyEstablishment $establishment, int $memberId): void
    {
        $member = $this->members->findOneInCompany($company, $memberId) ?? throw new CompanyMemberNotFoundException();

        if ($member->getRole() !== null) {
            return;
        }

        $role = $this->findEstablishmentRole($establishment, $member);

        if ($role === null) {
            $role = new EstablishmentMember();
            $member->addEstablishmentRole($role);
            $establishment->addMember($role);
            $this->orm->persist($role);
        }

        $role->setRole(EstablishmentRole::MANAGER)->setCanManageBookings(true);
    }

    private function findEstablishmentRole(
        CompanyEstablishment $establishment,
        CompanyMember $member,
    ): ?EstablishmentMember {
        if ($establishment->getId() === null) {
            return null;
        }

        return $this->establishmentMembers->findOneByEstablishmentAndMember($establishment, $member);
    }

    private function text(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return $value;
    }
}