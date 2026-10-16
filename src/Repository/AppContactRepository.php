<?php

namespace App\Repository;

use App\Entity\AppContact;
use App\Entity\User\User;
use App\Enum\Contact\AppContactStatus;
use NeoPHP\Package\Orm\Contract\AbstractRepository;
use NeoPHP\Package\Orm\Query\QueryBuilder;

class AppContactRepository extends AbstractRepository
{
    protected string $entityClass = AppContact::class;

    public function findForAdmin(?AppContactStatus $status, ?string $search, int $limit = 50, int $offset = 0): array
    {
        return $this->filtered($status, $search)
            ->orderBy('c.lastMessageAt', 'DESC')
            ->addOrderBy('c.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getResult();
    }

    public function countByStatus(AppContactStatus $status): int
    {
        return (int) $this->filtered($status, null)
            ->select('COUNT(c.id)')
            ->getSingleScalarResult();
    }

    public function findOneByToken(string $token): ?AppContact
    {
        $contact = $this->findOneBy(['token' => $token]);

        return $contact instanceof AppContact ? $contact : null;
    }

    public function findForUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user OR c.email = :email')
            ->setParameter('user', $user)
            ->setParameter('email', mb_strtolower((string) $user->getEmail()))
            ->orderBy('c.lastMessageAt', 'DESC')
            ->setMaxResults($limit)
            ->getResult();
    }

    private function filtered(?AppContactStatus $status, ?string $search): QueryBuilder
    {
        $builder = $this->createQueryBuilder('c');

        if ($status !== null) {
            $builder->andWhere('c.status = :status')->setParameter('status', $status);
        }

        if ($search !== null && trim($search) !== '') {
            $builder->andWhere('c.email LIKE :search OR c.reference LIKE :search')->setParameter('search', '%' . trim($search) . '%');
        }

        return $builder;
    }
}