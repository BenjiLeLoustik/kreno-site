<?php

namespace App\User\Service;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use App\User\Dto\ChangePasswordInput;
use App\User\Dto\ResetPasswordInput;
use App\User\Enum\UserStatus;
use App\User\Enum\UserTokenType;
use App\User\Event\PasswordChangedEvent;
use App\User\Event\PasswordResetRequestedEvent;
use App\User\Exception\PasswordMismatchException;
use App\User\Exception\SamePasswordException;
use App\User\Exception\WrongCurrentPasswordException;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;
use NeoPHP\Package\Security\Hasher\UserPasswordHasher;

class PasswordService
{
    public const SET_PASSWORD_TOKENS = [UserTokenType::PASSWORD_RESET, UserTokenType::ACCOUNT_SETUP];

    public const CLIENT_CONTEXT = 'client';

    public const CLOSED_STATUSES = [UserStatus::DELETED, UserStatus::BANNED];

    public function __construct(
        private OrmManagerInterface $orm,
        private UserRepository $users,
        private UserPasswordHasher $hasher,
        private UserTokenService $tokens,
        private EventManagerInterface $events,
    ) {
    }

    public function requestReset(string $email, string $context = self::CLIENT_CONTEXT): void
    {
        $user = $this->users->findOneByEmail($email);

        if ($user === null || in_array($user->getStatus(), self::CLOSED_STATUSES, true)) {
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
            throw new PasswordMismatchException();
        }

        $type = $this->tokenType($plainToken);
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
        if ($input->newPasswordConfirmation !== null && $input->newPasswordConfirmation !== $input->newPassword) {
            throw new PasswordMismatchException();
        }

        if (!$this->hasher->isPasswordValid($user, $input->currentPassword)) {
            throw new WrongCurrentPasswordException();
        }

        if ($input->currentPassword === $input->newPassword) {
            throw new SamePasswordException();
        }

        $user->setPassword($this->hasher->hashPassword($user, $input->newPassword));
        $this->orm->flush();

        $this->events->dispatch(new PasswordChangedEvent($user));

        return $user;
    }

    private function tokenType(string $plainToken): UserTokenType
    {
        return $this->tokens->find($plainToken, ...self::SET_PASSWORD_TOKENS)->getType()
            ?? UserTokenType::PASSWORD_RESET;
    }
}