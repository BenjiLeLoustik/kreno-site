<?php

namespace App\Repository\User;

use App\Entity\User\User;
use App\User\Enum\UserRole;
use App\User\Enum\UserStatus;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class UserRepository extends AbstractRepository
{
    protected string $entityClass = User::class;

    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => mb_strtolower(trim($email))]);
    }

    public function emailExists(string $email): bool
    {
        return $this->count(['email' => mb_strtolower(trim($email))]) > 0;
    }

    public function findStaff(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :admin OR u.roles LIKE :moderator OR u.roles LIKE :support')
            ->setParameter('admin', '%' . UserRole::ADMIN->value . '%')
            ->setParameter('moderator', '%' . UserRole::MODERATOR->value . '%')
            ->setParameter('support', '%' . UserRole::SUPPORT->value . '%')
            ->orderBy('u.firstName', 'ASC')
            ->getResult();
    }

    public function search(?string $query, ?UserStatus $status, int $limit = 50, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($query !== null && trim($query) !== '') {
            $qb->andWhere('u.email LIKE :q OR u.firstName LIKE :q OR u.lastName LIKE :q OR u.phone LIKE :q')
                ->setParameter('q', '%' . trim($query) . '%');
        }

        if ($status !== null) {
            $qb->andWhere('u.status = :status')->setParameter('status', $status);
        }

        return $qb->getResult();
    }

    public function findSuspensionsEndedBefore(DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.status = :status')
            ->andWhere('u.suspendedUntil <= :now')
            ->setParameter('status', UserStatus::SUSPENDED)
            ->setParameter('now', $now)
            ->getResult();
    }
}