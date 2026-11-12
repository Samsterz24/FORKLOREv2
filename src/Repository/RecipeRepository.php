<?php

namespace App\Repository;

use App\Entity\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }

    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.category = :category')
            ->setParameter('category', $category)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCuisine(int $cuisineId): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.cuisine', 'c')
            ->andWhere('c.id = :id')
            ->setParameter('id', $cuisineId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Recipe[]
     */
    public function searchAndFilter(string $search, string $sort, string $category, string $cuisine, string $allergy): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')->addSelect('u')
            ->leftJoin('r.cuisine', 'c')->addSelect('c');

        $search = trim($search);
        if ($search !== '') {
            $qb->andWhere('LOWER(r.title) LIKE :q OR LOWER(r.category) LIKE :q OR LOWER(COALESCE(r.ingredients, \'\')) LIKE :q OR LOWER(u.username) LIKE :q')
                ->setParameter('q', '%' . strtolower($search) . '%');
        }

        $category = trim($category);
        if ($category !== '' && strtolower($category) !== 'all') {
            $qb->andWhere('LOWER(r.category) = :category')
                ->setParameter('category', strtolower($category));
        }

        $cuisine = trim($cuisine);
        if ($cuisine !== '' && strtolower($cuisine) !== 'all') {
            $cuisineLower = strtolower($cuisine);
            $qb->andWhere('LOWER(c.slug) = :cuisine OR LOWER(c.name) = :cuisine')
                ->setParameter('cuisine', $cuisineLower);
        }

        $allergy = strtolower(trim($allergy));
        if ($allergy !== '') {
            $terms = match ($allergy) {
                'peanut' => ['peanut', 'peanuts'],
                'tree_nut', 'treenut' => ['almond', 'cashew', 'walnut', 'pecan', 'pistachio', 'hazelnut', 'macadamia', 'nut', 'nuts'],
                'milk', 'dairy' => ['milk', 'cheese', 'butter', 'cream', 'yogurt', 'dairy'],
                'egg', 'eggs' => ['egg', 'eggs'],
                'fish' => ['fish', 'salmon', 'tuna', 'cod', 'anchovy'],
                'shellfish' => ['shrimp', 'prawn', 'crab', 'lobster', 'clam', 'mussel', 'oyster', 'shellfish'],
                'soy' => ['soy', 'soya', 'tofu', 'edamame'],
                'wheat', 'gluten' => ['wheat', 'flour', 'bread', 'pasta', 'gluten'],
                default => [],
            };
            foreach ($terms as $i => $term) {
                $qb->andWhere('LOWER(COALESCE(r.ingredients, \'\')) NOT LIKE :allergy' . $i)
                    ->setParameter('allergy' . $i, '%' . $term . '%');
            }
        }

        switch ($sort) {
            case 'recent':
                $qb->orderBy('r.createdAt', 'DESC');
                break;
            case 'oldest':
                $qb->orderBy('r.createdAt', 'ASC');
                break;
            case 'favorites':
                $qb->orderBy('r.totalLikes', 'DESC')->addOrderBy('r.createdAt', 'DESC');
                break;
            case 'relevance':
            default:
                $qb->orderBy('r.totalLikes', 'DESC')->addOrderBy('r.createdAt', 'DESC');
                break;
        }

        return $qb->getQuery()->getResult();
    }
}