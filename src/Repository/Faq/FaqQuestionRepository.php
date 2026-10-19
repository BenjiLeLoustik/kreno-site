<?php

namespace App\Repository\Faq;

use App\Entity\Faq\FaqQuestion;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class FaqQuestionRepository extends AbstractRepository
{
    protected string $entityClass = FaqQuestion::class;

    public function findOneById(int $id): ?FaqQuestion
    {
        return $this->findOneBy(['id' => $id]);
    }
}