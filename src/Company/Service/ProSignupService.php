<?php

namespace App\Company\Service;

use App\Company\Dto\EstablishmentInput;
use App\Company\Dto\ProAccountInput;
use App\Company\Enum\CompanyRole;
use App\Company\Enum\CompanyStatus;
use App\Company\Enum\MemberStatus;
use App\Company\Event\ProAccountCreatedEvent;
use App\Company\Gateway\CompanyRegistryGateway;
use App\Company\Helper\SiretHelper;
use App\Company\Model\RegistryEstablishment;
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
use App\Shared\Exception\BusinessRuleException;
use App\User\Service\UserTokenService;
use App\User\Enum\UserRole;
use App\User\Enum\UserTokenType;
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
            throw new BusinessRuleException('Un compte existe déjà avec cette adresse : connectez-vous pour créer votre espace pro.');
        }

        if ($existing !== null && !$existing->getMemberships()->isEmpty()) {
            throw new BusinessRuleException('Votre compte est déjà rattaché à une entreprise.');
        }

        $user = $existing ?? (new User())->setEmail($email)->setRoles([UserRole::USER->value]);
        $user->setFirstName(trim($input->firstName))->setLastName(trim($input->lastName))->setPhone(trim($input->phone));

        if ($existing === null) {
            $user->setPassword($this->hasher->hashPassword($user, $input->password));
        }

        $company = (new Company())
            ->setName(trim($input->firstName . ' ' . $input->lastName))
            ->setKind($input->kind)
            ->setStatus(CompanyStatus::ONBOARDING);

        $company->setConfig((new CompanyConfig())->setDefaultDiscount($this->website->getRule()->getDefaultDiscount())->setBillingEmail($email));

        $member = (new CompanyMember())->setRole(CompanyRole::OWNER)->setStatus(MemberStatus::ACTIVE)->setJoinedAt(new DateTimeImmutable());
        $company->addMember($member);
        $user->addMembership($member);

        $this->orm->persist($user);
        $this->orm->persist($company);
        $this->orm->flush();

        $code = $this->tokens->createCode($user, UserTokenType::EMAIL_VERIFICATION);
        $this->orm->flush();

        $this->events->dispatch(new ProAccountCreatedEvent($user, $company, $code));

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
            throw new BusinessRuleException('Votre adresse e-mail est déjà vérifiée.');
        }

        $code = $this->tokens->createCode($user, UserTokenType::EMAIL_VERIFICATION);
        $this->orm->flush();

        $this->events->dispatch(new ProAccountCreatedEvent($user, $company, $code));
    }

    public function lookupSiret(string $siret): RegistryEstablishment
    {
        $siret = SiretHelper::normalize($siret);

        if (!SiretHelper::isValidSiret($siret)) {
            throw new BusinessRuleException('Ce numéro SIRET n\'est pas valide. Il contient 14 chiffres, il figure sur votre Kbis ou votre avis de situation INSEE.');
        }

        if ($this->establishments->findOneBySiret($siret) !== null) {
            throw new BusinessRuleException('Cet établissement est déjà inscrit sur Kréno. Contactez pro@kreno.fr si vous pensez qu\'il s\'agit d\'une erreur.');
        }

        $result = $this->registry->findBySiret($siret) ?? throw new BusinessRuleException('Nous n\'avons pas trouvé ce SIRET dans le registre public des entreprises.');

        if (!$result->active) {
            throw new BusinessRuleException('Ce SIRET correspond à un établissement fermé.');
        }

        return $result;
    }

    public function attachEstablishment(CompanyMember $owner, EstablishmentInput $input): CompanyEstablishment
    {
        $company = $owner->getCompany() ?? throw new BusinessRuleException('Entreprise introuvable.');

        if ($input->siret === null) {
            throw new BusinessRuleException('Le SIRET est obligatoire.');
        }

        $registry = $this->lookupSiret($input->siret);
        $city = $input->citySlug !== null ? $this->cities->findOneBySlug($input->citySlug) : null;

        if ($city === null || !in_array($city, $this->cities->findAvailableForPros(), true)) {
            throw new BusinessRuleException('Kréno n\'est pas encore disponible dans cette ville.');
        }

        $category = $input->categorySlug !== null ? $this->categories->findOneVisibleBySlug($input->categorySlug) : null;

        if ($category === null) {
            throw new BusinessRuleException('Choisissez la catégorie de votre établissement.');
        }

        $existing = $company->getEstablishments()->first();
        $establishment = $existing instanceof CompanyEstablishment && $existing->getStatus() === EstablishmentStatus::DRAFT ? $existing : new CompanyEstablishment();

        $establishment
            ->setName(trim($input->name ?? $registry->brandName ?? $registry->legalName))
            ->setSiret($registry->siret)
            ->setApeCode($registry->apeCode)
            ->setCity($city)
            ->setCategory($category)
            ->setAddressLine1($input->addressLine1 ?? $registry->address)
            ->setPostalCode($input->postalCode ?? $registry->postalCode)
            ->setLocality($input->locality ?? $registry->locality)
            ->setPhone($input->phone)
            ->setStatus(EstablishmentStatus::DRAFT);

        if ($registry->latitude !== null && $registry->longitude !== null) {
            $establishment->setLatitude(number_format($registry->latitude, 7, '.', ''))->setLongitude(number_format($registry->longitude, 7, '.', ''));
        }

        $company->setSiren($company->getSiren() ?? $registry->siren)->setLegalName($company->getLegalName() ?? $registry->legalName);

        if ($company->getEstablishments()->count() <= 1) {
            $company->setName((string) $establishment->getName());
        }

        $company->addEstablishment($establishment);
        $this->orm->persist($establishment);
        $this->orm->flush();

        return $establishment;
    }
}