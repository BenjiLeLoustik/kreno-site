<?php

namespace App\Repository\User;

use App\Entity\User\User;
use App\Entity\User\UserToken;
use App\User\Enum\UserTokenType;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class UserTokenRepository extends AbstractRepository
{
    protected string $entityClass = UserToken::class;

    public function findOneValid(string $tokenHash, UserTokenType $type, DateTimeImmutable $now): ?UserToken
    {
        return $this->createQueryBuilder('t')
            ->where('t.tokenHash = :hash')
            ->andWhere('t.type = :type')
            ->andWhere('t.usedAt IS NULL')
            ->andWhere('t.expiresAt > :now')
            ->setParameter('hash', $tokenHash)
            ->setParameter('type', $type)
            ->setParameter('now', $now)
            ->getOneOrNullResult();
    }

    public function findOneValidForUser(User $user, string $tokenHash, UserTokenType $type, DateTimeImmutable $now): ?UserToken
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.tokenHash = :hash')
            ->andWhere('t.type = :type')
            ->andWhere('t.usedAt IS NULL')
            ->andWhere('t.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('hash', $tokenHash)
            ->setParameter('type', $type)
            ->setParameter('now', $now)
            ->getOneOrNullResult();
    }

    public function findPending(User $user, UserTokenType $type): array
    {
        return $this->findBy(['user' => $user, 'type' => $type, 'usedAt' => null]);
    }

    public function findExpiredBefore(DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.expiresAt <= :now')
            ->setParameter('now', $now)
            ->getResult();
    }
}