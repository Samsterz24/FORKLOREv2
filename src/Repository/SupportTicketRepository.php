<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SupportTicket;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SupportTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportTicket::class);
    }

    /**
     * @return SupportTicket[]
     */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.updatedAt', 'DESC')
            ->getQuery()->getResult();
    }

    /**
     * @return SupportTicket[]
     */
    public function findForAdmin(?string $status = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.user', 'u')->addSelect('u')
            ->orderBy('t.updatedAt', 'DESC');

        if ($status && $status !== 'all') {
            $qb->andWhere('t.status = :status')->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }
}
