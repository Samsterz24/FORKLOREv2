<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class DashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $timeframe = (string) $request->query->get('leaderboard', 'all_time');
        if (!in_array($timeframe, ['daily', 'weekly', 'all_time'], true)) {
            $timeframe = 'all_time';
        }

        // All values below will be supplied by repositories and aggregate
        // queries in the persistence phase. Null statistics render as dashes.
        return $this->render('admin/dashboard.html.twig', [
            'timeframe' => $timeframe,
            'stats' => [
                'users' => null,
                'recipes' => null,
                'openTickets' => null,
                'logs' => null,
            ],
            'chart' => null,
            'leaderboard' => [],
            'recentTickets' => [],
            'recentLogs' => [],
        ]);
    }
}
