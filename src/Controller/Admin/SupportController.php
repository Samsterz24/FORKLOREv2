<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class SupportController extends AbstractController
{
    private const STATUSES = ['all', 'open', 'in_progress', 'closed'];

    #[Route('/support', name: 'admin_support', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $status = (string) $request->query->get('status', 'all');
        if (!in_array($status, self::STATUSES, true)) {
            $status = 'all';
        }

        $notice = null;
        if ($request->isMethod('POST')) {
            $notice = 'UI preview only — your reply and status change were accepted visually, but the ticket was not updated.';
        }

        // Tickets, messages, and the selected conversation will be provided by
        // the support repository once persistence is implemented.
        return $this->render('admin/support.html.twig', [
            'tickets' => [],
            'status' => $status,
            'selectedTicketId' => 0,
            'activeTicket' => null,
            'ticketMessages' => [],
            'notice' => $notice,
        ]);
    }
}
