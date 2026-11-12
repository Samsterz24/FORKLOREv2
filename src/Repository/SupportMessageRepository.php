<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SupportMessage;
use App\Entity\SupportTicket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SupportMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportMessage::class);
    }

    /**
     * @return SupportMessage[]
     */
    public function findForTicket(SupportTicket $ticket): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')->addSelect('s')
            ->andWhere('m.ticket = :ticket')
            ->setParameter('ticket', $ticket)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()->getResult();
    }

    public function findLastForTicket(SupportTicket $ticket): ?SupportMessage
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.ticket = :ticket')
            ->setParameter('ticket', $ticket)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }
}
