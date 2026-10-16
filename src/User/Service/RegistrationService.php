<?php

namespace App\User\Service;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use App\Shared\Exception\BusinessRuleException;
use App\User\Dto\RegistrationInput;
use App\User\Event\UserRegisteredEvent;
use App\User\Enum\UserRole;
use App\User\Enum\UserTokenType;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;
use NeoPHP\Package\Security\Hasher\UserPasswordHasher;

class RegistrationService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private UserRepository $users,
        private UserPasswordHasher $hasher,
        private UserTokenService $tokens,
        private EventManagerInterface $events,
    ) {
    }

    public function register(RegistrationInput $input): User
    {
        $email = mb_strtolower(trim($input->email));

        if ($this->users->emailExists($email)) {
            throw new BusinessRuleException('Un compte existe déjà avec cette adresse e-mail. Connectez-vous ou réinitialisez votre mot de passe.');
        }

        $user = (new User())
            ->setEmail($email)
            ->setFirstName(trim($input->firstName))
            ->setLastName($input->lastName !== null ? (trim($input->lastName) ?: null) : null)
            ->setPhone($input->phone !== null ? (trim($input->phone) ?: null) : null)
            ->setRoles([UserRole::USER->value]);

        $user->setPassword($this->hasher->hashPassword($user, $input->password));

        $this->orm->persist($user);
        $token = $this->tokens->create($user, UserTokenType::EMAIL_VERIFICATION);
        $this->orm->flush();

        $this->events->dispatch(new UserRegisteredEvent($user, $token));

        return $user;
    }

    public function verifyEmail(string $plainToken): User
    {
        $user = $this->tokens->consume($plainToken, UserTokenType::EMAIL_VERIFICATION);
        $user->setEmailVerifiedAt($user->getEmailVerifiedAt() ?? new DateTimeImmutable());
        $this->orm->flush();

        return $user;
    }

    public function resendVerification(User $user): void
    {
        if ($user->getEmailVerifiedAt() !== null) {
            throw new BusinessRuleException('Votre adresse e-mail est déjà vérifiée.');
        }

        $token = $this->tokens->create($user, UserTokenType::EMAIL_VERIFICATION);
        $this->orm->flush();

        $this->events->dispatch(new UserRegisteredEvent($user, $token));
    }
}