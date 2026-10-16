<?php

namespace App\Repository\Company;

use App\Billing\Enum\InvoiceStatus;
use App\Billing\Enum\InvoiceType;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyInvoice;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class CompanyInvoiceRepository extends AbstractRepository
{
    protected string $entityClass = CompanyInvoice::class;

    public function findForCompany(Company $company, int $limit = 50): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.company = :company')
            ->andWhere('i.status != :draft')
            ->setParameter('company', $company)
            ->setParameter('draft', InvoiceStatus::DRAFT)
            ->orderBy('i.issuedAt', 'DESC')
            ->addOrderBy('i.id', 'DESC')
            ->setMaxResults($limit)
            ->getResult();
    }

    public function findOneForCompany(Company $company, string $number): ?CompanyInvoice
    {
        return $this->findOneBy(['company' => $company, 'number' => mb_strtoupper($number)]);
    }

    public function findOneByNumber(string $number): ?CompanyInvoice
    {
        return $this->findOneBy(['number' => mb_strtoupper($number)]);
    }

    public function findUnpaidForCompany(Company $company): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.company = :company')
            ->andWhere('i.status IN (:statuses)')
            ->andWhere('i.type = :type')
            ->setParameter('company', $company)
            ->setParameter('statuses', [InvoiceStatus::OPEN, InvoiceStatus::FAILED])
            ->setParameter('type', InvoiceType::INVOICE)
            ->orderBy('i.issuedAt', 'ASC')
            ->getResult();
    }

    public function findLatestForCompany(Company $company): ?CompanyInvoice
    {
        return $this->createQueryBuilder('i')
            ->where('i.company = :company')
            ->andWhere('i.type = :type')
            ->andWhere('i.status != :draft')
            ->setParameter('company', $company)
            ->setParameter('type', InvoiceType::INVOICE)
            ->setParameter('draft', InvoiceStatus::DRAFT)
            ->orderBy('i.issuedAt', 'DESC')
            ->addOrderBy('i.id', 'DESC')
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function countNumbered(InvoiceType $type, int $year): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.type = :type')
            ->andWhere('i.number LIKE :prefix')
            ->setParameter('type', $type)
            ->setParameter('prefix', sprintf('%%-%d-%%', $year))
            ->getSingleScalarResult();
    }

    public function numberExists(string $number): bool
    {
        return $this->count(['number' => $number]) > 0;
    }

    public function sumPaidBetween(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('SUM(i.totalIncludingTax)')
            ->where('i.status = :status')
            ->andWhere('i.type = :type')
            ->andWhere('i.paidAt >= :from')
            ->andWhere('i.paidAt < :to')
            ->setParameter('status', InvoiceStatus::PAID)
            ->setParameter('type', InvoiceType::INVOICE)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getSingleScalarResult();
    }

    public function sumCreditNotesBetween(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('SUM(i.totalIncludingTax)')
            ->where('i.type = :type')
            ->andWhere('i.issuedAt >= :from')
            ->andWhere('i.issuedAt < :to')
            ->setParameter('type', InvoiceType::CREDIT_NOTE)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getSingleScalarResult();
    }

    public function countCreditNotesBetween(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.type = :type')
            ->andWhere('i.issuedAt >= :from')
            ->andWhere('i.issuedAt < :to')
            ->setParameter('type', InvoiceType::CREDIT_NOTE)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getSingleScalarResult();
    }

    public function commissionInvoiceExists(Company $company, DateTimeImmutable $periodStart): bool
    {
        return (int) $this->createQueryBuilder('i')
                ->select('COUNT(i.id)')
                ->where('i.company = :company')
                ->andWhere('i.subscription IS NULL')
                ->andWhere('i.type = :type')
                ->andWhere('i.periodStart = :periodStart')
                ->setParameter('company', $company)
                ->setParameter('type', InvoiceType::INVOICE)
                ->setParameter('periodStart', $periodStart)
                ->getSingleScalarResult() > 0;
    }
}