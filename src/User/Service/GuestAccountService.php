<?php

namespace App\User\Service;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use App\Shared\Exception\BusinessRuleException;
use App\User\Event\GuestAccountCreatedEvent;
use App\User\Enum\UserRole;
use App\User\Enum\UserTokenType;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;
use NeoPHP\Package\Security\Hasher\UserPasswordHasher;

class GuestAccountService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private EventManagerInterface $events,
        private UserPasswordHasher $hasher,
        private UserRepository $users,
        private UserTokenService $tokens,
    ) {
    }

    public function create(string $email, string $firstName, ?string $phone = null): User
    {
        $email = mb_strtolower(trim($email));

        if ($this->users->emailExists($email)) {
            throw new BusinessRuleException('Un compte existe déjà avec cette adresse e-mail : connecte-toi pour réserver.');
        }

        $phone = trim((string) $phone);

        $user = (new User())
            ->setEmail($email)
            ->setFirstName(trim($firstName))
            ->setPhone($phone !== '' ? $phone : null)
            ->setRoles([UserRole::USER->value]);

        $user->setPassword($this->hasher->hashPassword($user, bin2hex(random_bytes(24))));

        $this->orm->persist($user);
        $token = $this->tokens->create($user, UserTokenType::ACCOUNT_SETUP);
        $this->orm->flush();

        $this->events->dispatch(new GuestAccountCreatedEvent($user, $token));

        return $user;
    }
}