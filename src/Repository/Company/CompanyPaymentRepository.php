<?php

namespace App\Repository\Company;

use App\Billing\Enum\PaymentStatus;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyInvoice;
use App\Entity\Company\CompanyPayment;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class CompanyPaymentRepository extends AbstractRepository
{
    protected string $entityClass = CompanyPayment::class;

    public function findOneByProviderReference(string $reference): ?CompanyPayment
    {
        return $this->findOneBy(['providerReference' => $reference]);
    }

    public function findOneForCompany(Company $company, int $id): ?CompanyPayment
    {
        return $this->createQueryBuilder('p')
            ->join('p.invoice', 'i')
            ->where('p.id = :id')
            ->andWhere('i.company = :company')
            ->setParameter('id', $id)
            ->setParameter('company', $company)
            ->getOneOrNullResult();
    }

    public function findRetryDue(DateTimeImmutable $now, int $limit = 200): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.nextRetryAt IS NOT NULL')
            ->andWhere('p.nextRetryAt <= :now')
            ->setParameter('status', PaymentStatus::FAILED)
            ->setParameter('now', $now)
            ->setMaxResults($limit)
            ->getResult();
    }

    public function countFailedForInvoice(CompanyInvoice $invoice): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.invoice = :invoice')
            ->andWhere('p.status = :status')
            ->setParameter('invoice', $invoice)
            ->setParameter('status', PaymentStatus::FAILED)
            ->getSingleScalarResult();
    }

    public function countFailedBetween(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.status = :status')
            ->andWhere('p.attemptedAt >= :from')
            ->andWhere('p.attemptedAt < :to')
            ->andWhere('p.nextRetryAt IS NOT NULL')
            ->setParameter('status', PaymentStatus::FAILED)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getSingleScalarResult();
    }
}