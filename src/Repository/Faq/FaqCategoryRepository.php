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
        return $this->findBy(['audience' => $audience], ['position' => 'ASC']);
    }

    public function findVisibleForAudience(FaqAudience $audience, string $locale): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.translations', 't')
            ->where('c.audience = :audience')
            ->andWhere('c.visible = :visible')
            ->andWhere('t.locale = :locale')
            ->setParameter('audience', $audience)
            ->setParameter('visible', true)
            ->setParameter('locale', $locale)
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('t.name', 'ASC')
            ->getResult();
    }

    public function findOneBySlug(string $slug, string $locale): ?FaqCategory
    {
        return $this->createQueryBuilder('c')
            ->join('c.translations', 't')
            ->where('t.slug = :slug')
            ->andWhere('t.locale = :locale')
            ->setParameter('slug', $slug)
            ->setParameter('locale', $locale)
            ->getOneOrNullResult();
    }

    public function findOneByAnySlug(string $slug): ?FaqCategory
    {
        return $this->createQueryBuilder('c')
            ->join('c.translations', 't')
            ->where('t.slug = :slug')
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function findOneByAnySlugOrId(string $slug): ?FaqCategory
    {
        $category = $this->findOneByAnySlug($slug);

        if ($category === null && ctype_digit($slug)) {
            /** @var FaqCategory */
            return $this->find((int)$slug);
        }

        return $category;
    }

    public function nextPosition(FaqAudience $audience): int
    {
        return $this->count(['audience' => $audience]);
    }
}