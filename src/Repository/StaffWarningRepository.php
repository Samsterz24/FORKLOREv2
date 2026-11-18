<?php

namespace App\Repository;

use App\Entity\StaffWarning;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StaffWarningRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StaffWarning::class);
    }

    /** @return StaffWarning[] */
    public function findUnreadForUser(User $user): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.targetUser = :user')
            ->andWhere('w.seenAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countUnreadForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->andWhere('w.targetUser = :user')
            ->andWhere('w.seenAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAllReadForUser(User $user): void
    {
        $this->createQueryBuilder('w')
            ->update()
            ->set('w.seenAt', ':now')
            ->andWhere('w.targetUser = :user')
            ->andWhere('w.seenAt IS NULL')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
