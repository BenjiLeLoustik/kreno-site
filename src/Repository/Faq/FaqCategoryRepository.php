<?php

namespace App\Repository\Faq;

use App\Entity\Faq\FaqCategory;
use App\Faq\Enum\FaqAudience;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class FaqCategoryRepository extends AbstractRepository
{
    protected string $entityClass = FaqCategory::class;

    public function findForAudience(FaqAudience $audience): array
    {
        return $this->findBy(['audience' => $audience], ['position' => 'ASC', 'name' => 'ASC']);
    }

    public function findVisibleForAudience(FaqAudience $audience): array
    {
        return $this->findBy(['audience' => $audience, 'visible' => true], ['position' => 'ASC', 'name' => 'ASC']);
    }

    public function findOneBySlug(string $slug): ?FaqCategory
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function slugExists(string $slug): bool
    {
        return $this->count(['slug' => $slug]) > 0;
    }

    public function nextPosition(FaqAudience $audience): int
    {
        return $this->count(['audience' => $audience]);
    }
}