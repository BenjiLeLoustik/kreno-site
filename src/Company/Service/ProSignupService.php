<?php

namespace App\Company\Service;

use App\Company\Dto\EstablishmentInput;
use App\Company\Dto\ProAccountInput;
use App\Company\Enum\CompanyRole;
use App\Company\Enum\CompanyStatus;
use App\Company\Enum\MemberStatus;
use App\Company\Event\ProAccountCreatedEvent;
use App\Company\Exception\AccountAlreadyLinkedException;
use App\Company\Exception\CategoryRequiredException;
use App\Company\Exception\CityUnavailableException;
use App\Company\Exception\ClosedEstablishmentException;
use App\Company\Exception\CompanyNotFoundException;
use App\Company\Exception\EmailAlreadyRegisteredException;
use App\Company\Exception\InvalidSiretException;
use App\Company\Exception\SiretAlreadyRegisteredException;
use App\Company\Exception\SiretNotFoundException;
use App\Company\Exception\SiretRequiredException;
use App\Company\Gateway\CompanyRegistryGateway;
use App\Company\Helper\SiretHelper;
use App\Company\Model\RegistryEstablishment;
use App\Entity\Category;
use App\Entity\City;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyConfig;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentStatus;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Repository\User\UserRepository;
use App\Shared\Helper\CoordinateHelper;
use App\User\Enum\UserRole;
use App\User\Enum\UserTokenType;
use App\User\Exception\EmailAlreadyVerifiedException;
use App\User\Service\UserTokenService;
use App\Website\Provider\WebsiteProvider;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;
use NeoPHP\Package\Security\Hasher\UserPasswordHasher;

class ProSignupService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private UserRepository $users,
        private CityRepository $cities,
        private CategoryRepository $categories,
        private CompanyEstablishmentRepository $establishments,
        private UserPasswordHasher $hasher,
        private UserTokenService $tokens,
        private CompanyRegistryGateway $registry,
        private WebsiteProvider $website,
        private EventManagerInterface $events,
    ) {
    }

    public function createAccount(ProAccountInput $input, ?User $existing = null): User
    {
        $email = mb_strtolower(trim($input->email));

        if ($existing === null && $this->users->emailExists($email)) {
            throw new EmailAlreadyRegisteredException();
        }

        if ($existing !== null && !$existing->getMemberships()->isEmpty()) {
            throw new AccountAlreadyLinkedException();
        }

        $user = $this->user($input, $email, $existing);
        $company = $this->company($input, $email);

        $member = (new CompanyMember())
            ->setRole(CompanyRole::OWNER)
            ->setStatus(MemberStatus::ACTIVE)
            ->setJoinedAt(new DateTimeImmutable());

        $company->addMember($member);
        $user->addMembership($member);

        $this->orm->persist($user);
        $this->orm->persist($company);
        $this->orm->flush();

        $this->sendCode($user, $company);

        return $user;
    }

    public function verifyCode(User $user, string $code): User
    {
        $this->tokens->consumeCode($user, $code, UserTokenType::EMAIL_VERIFICATION);
        $user->setEmailVerifiedAt(new DateTimeImmutable());
        $this->orm->flush();

        return $user;
    }

    public function resendCode(User $user, Company $company): void
    {
        if ($user->getEmailVerifiedAt() !== null) {
            throw new EmailAlreadyVerifiedException();
        }

        $this->sendCode($user, $company);
    }

    public function lookupSiret(string $siret): RegistryEstablishment
    {
        $siret = SiretHelper::normalize($siret);

        if (!SiretHelper::isValidSiret($siret)) {
            throw new InvalidSiretException();
        }

        if ($this->establishments->findOneBySiret($siret) !== null) {
            throw new SiretAlreadyRegisteredException();
        }

        $result = $this->registry->findBySiret($siret) ?? throw new SiretNotFoundException();

        if (!$result->active) {
            throw new ClosedEstablishmentException();
        }

        return $result;
    }

    public function attachEstablishment(CompanyMember $owner, EstablishmentInput $input): CompanyEstablishment
    {
        $company = $owner->getCompany() ?? throw new CompanyNotFoundException();

        if ($input->siret === null) {
            throw new SiretRequiredException();
        }

        $registry = $this->lookupSiret($input->siret);
        $establishment = $this->draftEstablishment($company);

        $establishment
            ->setName(trim($input->name ?? $registry->brandName ?? $registry->legalName))
            ->setSiret($registry->siret)
            ->setApeCode($registry->apeCode)
            ->setCity($this->availableCity($input->citySlug))
            ->setCategory($this->category($input->categorySlug))
            ->setAddressLine1($input->addressLine1 ?? $registry->address)
            ->setPostalCode($input->postalCode ?? $registry->postalCode)
            ->setLocality($input->locality ?? $registry->locality)
            ->setPhone($input->phone)
            ->setStatus(EstablishmentStatus::DRAFT);

        $this->applyCoordinates($establishment, $registry);

        $company
            ->setSiren($company->getSiren() ?? $registry->siren)
            ->setLegalName($company->getLegalName() ?? $registry->legalName);

        if ($company->getEstablishments()->count() <= 1) {
            $company->setName((string) $establishment->getName());
        }

        $company->addEstablishment($establishment);
        $this->orm->persist($establishment);
        $this->orm->flush();

        return $establishment;
    }

    private function user(ProAccountInput $input, string $email, ?User $existing): User
    {
        $user = $existing ?? (new User())->setEmail($email)->setRoles([UserRole::USER->value]);

        $user
            ->setFirstName(trim($input->firstName))
            ->setLastName(trim($input->lastName))
            ->setPhone(trim($input->phone));

        if ($existing === null) {
            $user->setPassword($this->hasher->hashPassword($user, $input->password));
        }

        return $user;
    }

    private function company(ProAccountInput $input, string $email): Company
    {
        $config = (new CompanyConfig())
            ->setDefaultDiscount($this->website->getRule()->getDefaultDiscount())
            ->setBillingEmail($email);

        $company = (new Company())
            ->setName(trim($input->firstName . ' ' . $input->lastName))
            ->setKind($input->kind)
            ->setStatus(CompanyStatus::ONBOARDING);

        $company->setConfig($config);

        return $company;
    }

    private function sendCode(User $user, Company $company): void
    {
        $code = $this->tokens->createCode($user, UserTokenType::EMAIL_VERIFICATION);
        $this->orm->flush();

        $this->events->dispatch(new ProAccountCreatedEvent($user, $company, $code));
    }

    private function availableCity(?string $slug): City
    {
        $city = null;

        if ($slug !== null) {
            $city = $this->cities->findOneBySlug($slug);
        }

        if ($city === null || !in_array($city, $this->cities->findAvailableForPros(), true)) {
            throw new CityUnavailableException();
        }

        return $city;
    }

    private function category(?string $slug): Category
    {
        if ($slug === null) {
            throw new CategoryRequiredException();
        }

        return $this->categories->findOneVisibleBySlug($slug) ?? throw new CategoryRequiredException();
    }

    private function draftEstablishment(Company $company): CompanyEstablishment
    {
        $existing = $company->getEstablishments()->first();

        if ($existing instanceof CompanyEstablishment && $existing->getStatus() === EstablishmentStatus::DRAFT) {
            return $existing;
        }

        return new CompanyEstablishment();
    }

    private function applyCoordinates(CompanyEstablishment $establishment, RegistryEstablishment $registry): void
    {
        if ($registry->latitude === null || $registry->longitude === null) {
            return;
        }

        $establishment
            ->setLatitude(CoordinateHelper::format($registry->latitude))
            ->setLongitude(CoordinateHelper::format($registry->longitude));
    }
}