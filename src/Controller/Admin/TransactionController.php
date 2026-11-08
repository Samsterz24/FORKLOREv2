<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class TransactionController extends AbstractController
{
    #[Route('/transactions', name: 'admin_transactions', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $method = trim((string) $request->query->get('method', 'all'));
        $status = trim((string) $request->query->get('status', 'all'));
        $search = trim((string) $request->query->get('search', ''));

        // Transactions, payment methods, and status options will come from the
        // transaction repository in the persistence phase.
        return $this->render('admin/transactions.html.twig', [
            'transactions' => [],
            'method' => $method,
            'status' => $status,
            'search' => $search,
            'methods' => [],
            'statuses' => [],
            'stats' => [
                'revenue' => null,
                'completed' => null,
                'activeStamps' => null,
                'topMethod' => null,
            ],
        ]);
    }
}
