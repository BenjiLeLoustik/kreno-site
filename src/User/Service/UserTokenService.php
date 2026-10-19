<?php

namespace App\User\Service;

use App\Entity\User\User;
use App\Entity\User\UserToken;
use App\Repository\User\UserTokenRepository;
use App\User\Enum\UserTokenType;
use App\User\Exception\InvalidCodeException;
use App\User\Exception\InvalidTokenException;
use App\User\Helper\TokenHelper;
use DateTimeImmutable;
use NeoPHP\Package\Orm\OrmManagerInterface;

class UserTokenService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private UserTokenRepository $tokens,
    ) {
    }

    public function create(User $user, UserTokenType $type): string
    {
        $plain = TokenHelper::generate();

        $this->store($user, $type, TokenHelper::hash($plain), $type->lifetime());

        return $plain;
    }

    public function createCode(User $user, UserTokenType $type): string
    {
        $code = TokenHelper::code();

        $this->store($user, $type, $this->codeHash($user, $code), UserTokenType::CODE_LIFETIME);

        return $code;
    }

    public function consumeCode(User $user, string $code, UserTokenType $type): void
    {
        $hash = $this->codeHash($user, trim($code));
        $token = $this->tokens->findOneValidForUser($user, $hash, $type, new DateTimeImmutable());

        if ($token === null) {
            throw new InvalidCodeException();
        }

        $token->setUsedAt(new DateTimeImmutable());
    }

    public function find(string $plainToken, UserTokenType ...$types): UserToken
    {
        $now = new DateTimeImmutable();

        foreach ($types as $type) {
            $token = $this->tokens->findOneValid(TokenHelper::hash($plainToken), $type, $now);

            if ($token !== null) {
                return $token;
            }
        }

        throw new InvalidTokenException();
    }

    public function consume(string $plainToken, UserTokenType ...$types): User
    {
        $token = $this->find($plainToken, ...$types);
        $token->setUsedAt(new DateTimeImmutable());

        if ($token->getUser() === null) {
            throw new InvalidTokenException();
        }

        return $token->getUser();
    }

    public function purgeExpired(): int
    {
        $expired = $this->tokens->findExpiredBefore(new DateTimeImmutable());

        foreach ($expired as $token) {
            $this->orm->remove($token);
        }

        $this->orm->flush();

        return count($expired);
    }

    private function store(User $user, UserTokenType $type, string $hash, string $lifetime): void
    {
        $this->invalidatePending($user, $type);

        $token = (new UserToken())
            ->setType($type)
            ->setTokenHash($hash)
            ->setExpiresAt(new DateTimeImmutable($lifetime));

        $user->addToken($token);
        $this->orm->persist($token);
    }

    private function codeHash(User $user, string $code): string
    {
        return TokenHelper::hash($user->getId() . ':' . $code);
    }

    private function invalidatePending(User $user, UserTokenType $type): void
    {
        foreach ($this->tokens->findPending($user, $type) as $pending) {
            $pending->setUsedAt(new DateTimeImmutable());
        }
    }
}