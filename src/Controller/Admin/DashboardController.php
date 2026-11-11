<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Recipe;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class DashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $timeframe = (string) $request->query->get('leaderboard', 'all_time');
        if (!in_array($timeframe, ['daily', 'weekly', 'all_time'], true)) {
            $timeframe = 'all_time';
        }

        $userCount = (int) $em->getRepository(User::class)->count([]);
        $recipeCount = (int) $em->getRepository(Recipe::class)->count([]);

        $qb = $em->getRepository(Recipe::class)->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')
            ->addSelect('u')
            ->orderBy('r.totalLikes', 'DESC')
            ->addOrderBy('r.createdAt', 'DESC')
            ->setMaxResults(10);

        $now = new \DateTimeImmutable();
        if ($timeframe === 'daily') {
            $qb->andWhere('r.createdAt >= :since')->setParameter('since', $now->modify('-1 day'));
        } elseif ($timeframe === 'weekly') {
            $qb->andWhere('r.createdAt >= :since')->setParameter('since', $now->modify('-7 days'));
        }

        $recipes = $qb->getQuery()->getResult();
        $leaderboard = array_map(static function (Recipe $recipe): array {
            return [
                'id' => $recipe->getId(),
                'title' => $recipe->getTitle(),
                'category' => $recipe->getCategory(),
                'author' => $recipe->getUser()?->getUsername() ?? 'Unknown',
                'totalLikes' => $recipe->getTotalLikes(),
            ];
        }, $recipes);

        return $this->render('admin/dashboard.html.twig', [
            'timeframe' => $timeframe,
            'stats' => [
                'users' => $userCount,
                'recipes' => $recipeCount,
                'openTickets' => null,
                'logs' => null,
            ],
            'chart' => null,
            'leaderboard' => $leaderboard,
            'recentTickets' => [],
            'recentLogs' => [],
        ]);
    }
}
