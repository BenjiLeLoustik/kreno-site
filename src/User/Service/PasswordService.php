<?php

namespace App\User\Service;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use App\Shared\Exception\BusinessRuleException;
use App\User\Dto\ChangePasswordInput;
use App\User\Dto\ResetPasswordInput;
use App\User\Event\PasswordChangedEvent;
use App\User\Event\PasswordResetRequestedEvent;
use App\User\Enum\UserStatus;
use App\User\Enum\UserTokenType;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;
use NeoPHP\Package\Security\Hasher\UserPasswordHasher;

class PasswordService
{
    public const SET_PASSWORD_TOKENS = [UserTokenType::PASSWORD_RESET, UserTokenType::ACCOUNT_SETUP];

    public function __construct(
        private OrmManagerInterface $orm,
        private UserRepository $users,
        private UserPasswordHasher $hasher,
        private UserTokenService $tokens,
        private EventManagerInterface $events,
    ) {
    }

    public function requestReset(string $email, string $context = 'client'): void
    {
        $user = $this->users->findOneByEmail($email);

        if ($user === null || in_array($user->getStatus(), [UserStatus::DELETED, UserStatus::BANNED], true)) {
            return;
        }

        $token = $this->tokens->create($user, UserTokenType::PASSWORD_RESET);
        $this->orm->flush();

        $this->events->dispatch(new PasswordResetRequestedEvent($user, $token, $context));
    }

    public function checkResetToken(string $plainToken): User
    {
        return $this->tokens->find($plainToken, ...self::SET_PASSWORD_TOKENS)->getUser();
    }

    public function reset(string $plainToken, ResetPasswordInput $input): User
    {
        if ($input->password !== $input->passwordConfirmation) {
            throw new BusinessRuleException('Les deux mots de passe ne sont pas identiques.');
        }

        $type = $this->tokens->find($plainToken, ...self::SET_PASSWORD_TOKENS)->getType() ?? UserTokenType::PASSWORD_RESET;
        $user = $this->tokens->consume($plainToken, $type);
        $user->setPassword($this->hasher->hashPassword($user, $input->password));

        if ($user->getEmailVerifiedAt() === null) {
            $user->setEmailVerifiedAt(new DateTimeImmutable());
        }

        $this->orm->flush();

        if ($type !== UserTokenType::ACCOUNT_SETUP) {
            $this->events->dispatch(new PasswordChangedEvent($user));
        }

        return $user;
    }

    public function change(User $user, ChangePasswordInput $input): User
    {
        if (!$this->hasher->isPasswordValid($user, $input->currentPassword)) {
            throw new BusinessRuleException('Le mot de passe actuel est incorrect.');
        }

        if ($input->currentPassword === $input->newPassword) {
            throw new BusinessRuleException('Le nouveau mot de passe doit être différent de l\'actuel.');
        }

        $user->setPassword($this->hasher->hashPassword($user, $input->newPassword));
        $this->orm->flush();

        $this->events->dispatch(new PasswordChangedEvent($user));

        return $user;
    }
}