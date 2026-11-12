<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Recipe;
use App\Entity\SupportTicket;
use App\Entity\User;
use App\Repository\SupportMessageRepository;
use App\Repository\SupportTicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class DashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        SupportTicketRepository $ticketRepository,
        SupportMessageRepository $messageRepository,
    ): Response {
        $timeframe = (string) $request->query->get('leaderboard', 'all_time');
        if (!in_array($timeframe, ['daily', 'weekly', 'all_time'], true)) {
            $timeframe = 'all_time';
        }

        $userCount = (int) $em->getRepository(User::class)->count([]);
        $recipeCount = (int) $em->getRepository(Recipe::class)->count([]);
        $openTickets = (int) $em->getRepository(SupportTicket::class)->count(['status' => 'open'])
            + (int) $em->getRepository(SupportTicket::class)->count(['status' => 'in_progress']);

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

        $recent = $ticketRepository->findBy([], ['updatedAt' => 'DESC'], 5);
        $recentTickets = [];
        foreach ($recent as $t) {
            $recentTickets[] = [
                'id' => $t->getId(),
                'subject' => $t->getSubject(),
                'status' => $t->getStatus(),
                'createdAt' => $t->getCreatedAt(),
                'user' => $t->getUser()->getUsername(),
            ];
        }

        return $this->render('admin/dashboard.html.twig', [
            'timeframe' => $timeframe,
            'stats' => [
                'users' => $userCount,
                'recipes' => $recipeCount,
                'openTickets' => $openTickets,
                'logs' => null,
            ],
            'chart' => null,
            'leaderboard' => $leaderboard,
            'recentTickets' => $recentTickets,
            'recentLogs' => [],
        ]);
    }
}
