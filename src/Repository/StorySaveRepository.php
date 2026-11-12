<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Story;
use App\Entity\StorySave;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StorySaveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StorySave::class);
    }

    public function findOneByUserAndStory(User $user, Story $story): ?StorySave
    {
        return $this->findOneBy(['user' => $user, 'story' => $story]);
    }

    /** @return Story[] */
    public function findStoriesSavedByUser(User $user): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('s')
            ->from(Story::class, 's')
            ->innerJoin(StorySave::class, 'ss', 'WITH', 'ss.story = s')
            ->andWhere('ss.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ss.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
