<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class TransactionController extends AbstractController
{
    #[Route('/transactions', name: 'admin_transactions', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $method = trim((string) $request->query->get('method', 'all'));
        $status = trim((string) $request->query->get('status', 'all'));
        $search = trim((string) $request->query->get('search', ''));

        $subscriptions = $em->getRepository(Subscription::class)->findBy([], ['createdAt' => 'DESC']);

        $all = array_map(static function (Subscription $sub): array {
            $user = $sub->getUser();
            $plan = strtolower($sub->getPlan());
            $amount = str_contains($plan, 'year') ? 29.99 : 2.99;
            $subStatus = strtolower($sub->getStatus());
            $isActive = $subStatus === 'active' && ($sub->getExpiresAt() === null || $sub->getExpiresAt() > new \DateTimeImmutable());
            $derivedMethod = str_contains($plan, 'year') ? 'Mock Card' : 'Mock GCash';

            return [
                'id' => $sub->getId(),
                'reference' => sprintf('SUB-%06d', (int) $sub->getId()),
                'user' => $user->getUsername(),
                'email' => $user->getEmail(),
                'amount' => $amount,
                'method' => $derivedMethod,
                'status' => $subStatus ?: 'unknown',
                'foodieActive' => $isActive,
                'expires' => $sub->getExpiresAt(),
                'createdAt' => $sub->getCreatedAt(),
            ];
        }, $subscriptions);

        $statuses = array_values(array_unique(array_map(fn($t) => $t['status'], $all)));
        sort($statuses);
        $methods = array_values(array_unique(array_map(fn($t) => $t['method'], $all)));
        sort($methods);

        $filtered = $all;
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $filtered = array_values(array_filter($filtered, function ($t) use ($needle) {
                return str_contains(mb_strtolower($t['reference']), $needle)
                    || str_contains(mb_strtolower($t['user']), $needle)
                    || str_contains(mb_strtolower($t['email']), $needle);
            }));
        }
        if ($method !== '' && strtolower($method) !== 'all') {
            $filtered = array_values(array_filter($filtered, fn($t) => strcasecmp($t['method'], $method) === 0));
        }
        if ($status !== '' && strtolower($status) !== 'all') {
            $filtered = array_values(array_filter($filtered, fn($t) => strcasecmp($t['status'], $status) === 0));
        }

        $completed = array_values(array_filter($all, fn($t) => $t['status'] === 'completed' || $t['status'] === 'active'));
        $revenue = array_reduce($completed, fn($sum, $t) => $sum + (float) $t['amount'], 0.0);
        $activeStamps = count(array_filter($all, fn($t) => !empty($t['foodieActive'])));

        $methodCounts = [];
        foreach ($all as $t) {
            $methodCounts[$t['method']] = ($methodCounts[$t['method']] ?? 0) + 1;
        }
        arsort($methodCounts);
        $topMethod = $methodCounts ? (string) array_key_first($methodCounts) : null;

        return $this->render('admin/transactions.html.twig', [
            'transactions' => $filtered,
            'method' => $method,
            'status' => $status,
            'search' => $search,
            'methods' => $methods,
            'statuses' => $statuses,
            'stats' => [
                'revenue' => $all ? $revenue : null,
                'completed' => $all ? count($completed) : null,
                'activeStamps' => $all ? $activeStamps : null,
                'topMethod' => $topMethod,
            ],
        ]);
    }
}
