<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Favorite;
use App\Entity\Post;
use App\Entity\Recipe;
use App\Entity\Reply;
use App\Entity\StaffWarning;
use App\Entity\StorySave;
use App\Entity\SupportMessage;
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
        $staffCount = (int) $em->getRepository(User::class)->count(['role' => 'ROLE_STAFF']);
        $recipeCount = (int) $em->getRepository(Recipe::class)->count([]);
        $openTickets = (int) $em->getRepository(SupportTicket::class)->count(['status' => 'open'])
            + (int) $em->getRepository(SupportTicket::class)->count(['status' => 'in_progress']);
        $totalRecords = $recipeCount
            + (int) $em->getRepository(\App\Entity\Story::class)->count([])
            + (int) $em->getRepository(Post::class)->count([])
            + (int) $em->getRepository(Reply::class)->count([])
            + (int) $em->getRepository(SupportTicket::class)->count([]);

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

        $warningRows = $em->getRepository(StaffWarning::class)->findBy([], ['createdAt' => 'DESC'], 20);
        $recentWarnings = array_map(static fn(StaffWarning $w): array => [
            'id' => $w->getId(),
            'staff' => $w->getStaff()->getUsername(),
            'target' => $w->getTargetUser()->getUsername(),
            'targetId' => $w->getTargetUser()->getId(),
            'targetUsername' => $w->getTargetUser()->getUsername(),
            'reason' => $w->getReason(),
            'createdAt' => $w->getCreatedAt(),
        ], $warningRows);

        $chart = $this->buildInteractionChart($em, $now);

        return $this->render('admin/dashboard.html.twig', [
            'timeframe' => $timeframe,
            'stats' => [
                'users' => $userCount,
                'staff' => $staffCount,
                'recipes' => $recipeCount,
                'openTickets' => $openTickets,
                'totalRecords' => $totalRecords,
                'logs' => count($recentWarnings),
            ],
            'chart' => $chart,
            'leaderboard' => $leaderboard,
            'recentTickets' => $recentTickets,
            'recentLogs' => [],
            'recentWarnings' => $recentWarnings,
        ]);
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    private function buildInteractionChart(EntityManagerInterface $em, \DateTimeImmutable $now): array
    {
        $start = $now->modify('-6 days')->setTime(0, 0, 0);
        $labels = [];
        $values = [];
        $countsByDay = [];

        for ($i = 0; $i < 7; $i++) {
            $day = $start->modify('+' . $i . ' days');
            $key = $day->format('Y-m-d');
            $labels[] = $day->format('M j');
            $countsByDay[$key] = 0;
        }

        $end = $start->modify('+7 days');
        $interactionEntities = [
            Reply::class,
            Favorite::class,
            StorySave::class,
            SupportMessage::class,
            Post::class,
        ];
        $conn = $em->getConnection();

        foreach ($interactionEntities as $entityClass) {
            $meta = $em->getClassMetadata($entityClass);
            $table = $meta->getTableName();
            $createdAtColumn = $meta->getColumnName('createdAt');

            $sql = sprintf(
                'SELECT DATE(%s) AS day_key, COUNT(*) AS cnt FROM %s WHERE %s >= :start AND %s < :end GROUP BY day_key',
                $createdAtColumn,
                $table,
                $createdAtColumn,
                $createdAtColumn
            );

            $rows = $conn->fetchAllAssociative($sql, [
                'start' => $start->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
            ]);

            foreach ($rows as $row) {
                $key = (string) ($row['day_key'] ?? '');
                if (isset($countsByDay[$key])) {
                    $countsByDay[$key] += (int) ($row['cnt'] ?? 0);
                }
            }
        }

        foreach ($countsByDay as $count) {
            $values[] = $count;
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}
