<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AppContactMessage;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class AppContactMessageRepository extends AbstractRepository
{
    protected string $entityClass = AppContactMessage::class;
}
