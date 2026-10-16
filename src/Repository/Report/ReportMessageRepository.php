<?php

namespace App\Repository\Report;

use App\Entity\Report\ReportMessage;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class ReportMessageRepository extends AbstractRepository
{
    protected string $entityClass = ReportMessage::class;
}