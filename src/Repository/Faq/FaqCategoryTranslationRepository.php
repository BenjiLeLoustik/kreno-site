<?php

declare(strict_types=1);

namespace App\Repository\Faq;

use App\Entity\Faq\FaqCategory;
use App\Entity\Faq\FaqCategoryTranslation;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class FaqCategoryTranslationRepository extends AbstractRepository
{
    protected string $entityClass = FaqCategoryTranslation::class;

    public function findOneByCategoryAndLocale(FaqCategory $category, string $locale): ?FaqCategoryTranslation
    {
        return $this->findOneBy([
            'category' => $category,
            'locale' => $locale,
        ]);
    }

    public function slugExists(string $slug, string $locale, ?int $excludeId = null): bool
    {
        $builder = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.slug = :slug')
            ->andWhere('t.locale = :locale')
            ->setParameter('slug', $slug)
            ->setParameter('locale', $locale);

        if ($excludeId !== null) {
            $builder
                ->andWhere('t.id != :id')
                ->setParameter('id', $excludeId);
        }

        return (int) $builder->getSingleScalarResult() > 0;
    }
}