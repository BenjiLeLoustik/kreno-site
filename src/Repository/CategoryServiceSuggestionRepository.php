<?php

namespace App\Repository;

use App\Entity\CategoryServiceSuggestion;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class CategoryServiceSuggestionRepository extends AbstractRepository
{
    protected string $entityClass = CategoryServiceSuggestion::class;

    public function findOneById(int $id): ?CategoryServiceSuggestion
    {
        return $this->findOneBy(['id' => $id]);
    }
}