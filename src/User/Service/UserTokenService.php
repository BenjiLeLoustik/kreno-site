<?php

namespace App\User\Service;

use App\Entity\User\User;
use App\Entity\User\UserToken;
use App\Repository\User\UserTokenRepository;
use App\Shared\Exception\BusinessRuleException;
use App\User\Exception\InvalidTokenException;
use App\User\Helper\TokenHelper;
use App\User\Enum\UserTokenType;
use DateTimeImmutable;
use NeoPHP\Package\Orm\OrmManagerInterface;

class UserTokenService
{
    private const LIFETIMES = [
        'email_verification' => '+7 days',
        'password_reset' => '+1 hour',
        'email_change' => '+1 day',
        'account_setup' => '+7 days',
    ];

    private const CODE_LIFETIME = '+30 minutes';

    public function __construct(
        private OrmManagerInterface $orm,
        private UserTokenRepository $tokens,
    ) {
    }

    public function create(User $user, UserTokenType $type): string
    {
        $this->invalidatePending($user, $type);
        $plain = TokenHelper::generate();

        $token = (new UserToken())
            ->setType($type)
            ->setTokenHash(TokenHelper::hash($plain))
            ->setExpiresAt(new DateTimeImmutable(self::LIFETIMES[$type->value]));

        $user->addToken($token);
        $this->orm->persist($token);

        return $plain;
    }

    public function createCode(User $user, UserTokenType $type): string
    {
        $this->invalidatePending($user, $type);
        $code = TokenHelper::code();

        $token = (new UserToken())
            ->setType($type)
            ->setTokenHash(TokenHelper::hash($user->getId() . ':' . $code))
            ->setExpiresAt(new DateTimeImmutable(self::CODE_LIFETIME));

        $user->addToken($token);
        $this->orm->persist($token);

        return $code;
    }

    public function consumeCode(User $user, string $code, UserTokenType $type): void
    {
        $token = $this->tokens->findOneValidForUser($user, TokenHelper::hash($user->getId() . ':' . trim($code)), $type, new DateTimeImmutable())
            ?? throw new BusinessRuleException('Ce code ne correspond pas ou a expiré. Vérifiez le dernier e-mail reçu, ou demandez-en un nouveau.');

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

        return $token->getUser() ?? throw new InvalidTokenException();
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

    private function invalidatePending(User $user, UserTokenType $type): void
    {
        foreach ($this->tokens->findPending($user, $type) as $pending) {
            $pending->setUsedAt(new DateTimeImmutable());
        }
    }
}