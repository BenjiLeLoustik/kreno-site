<?php

namespace App\Repository\Company;

use App\Entity\Company\Company;
use App\Entity\Company\CompanyInvitation;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class CompanyInvitationRepository extends AbstractRepository
{
    protected string $entityClass = CompanyInvitation::class;

    public function findOneValid(string $tokenHash, DateTimeImmutable $now): ?CompanyInvitation
    {
        return $this->createQueryBuilder('i')
            ->where('i.tokenHash = :hash')
            ->andWhere('i.acceptedAt IS NULL')
            ->andWhere('i.expiresAt > :now')
            ->setParameter('hash', $tokenHash)
            ->setParameter('now', $now)
            ->getOneOrNullResult();
    }

    public function findPendingForCompany(Company $company, DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.company = :company')
            ->andWhere('i.acceptedAt IS NULL')
            ->andWhere('i.expiresAt > :now')
            ->setParameter('company', $company)
            ->setParameter('now', $now)
            ->orderBy('i.createdAt', 'DESC')
            ->getResult();
    }

    public function findPendingForEmail(Company $company, string $email, DateTimeImmutable $now): ?CompanyInvitation
    {
        return $this->createQueryBuilder('i')
            ->where('i.company = :company')
            ->andWhere('i.email = :email')
            ->andWhere('i.acceptedAt IS NULL')
            ->andWhere('i.expiresAt > :now')
            ->setParameter('company', $company)
            ->setParameter('email', mb_strtolower(trim($email)))
            ->setParameter('now', $now)
            ->getOneOrNullResult();
    }
}