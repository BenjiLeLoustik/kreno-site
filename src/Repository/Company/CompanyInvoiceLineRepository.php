<?php

namespace App\Repository\Company;

use App\Entity\Company\CompanyInvoiceLine;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class CompanyInvoiceLineRepository extends AbstractRepository
{
    protected string $entityClass = CompanyInvoiceLine::class;

    public function sumCommissionsBetween(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('SUM(l.total)')
            ->join('l.invoice', 'i')
            ->where('l.establishment IS NOT NULL')
            ->andWhere('i.periodStart >= :from')
            ->andWhere('i.periodStart < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getSingleScalarResult();
    }
}