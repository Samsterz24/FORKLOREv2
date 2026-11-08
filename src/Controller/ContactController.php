<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        // Support tickets will arrive from the ticket repository in the
        // persistence phase. With no records, the page opens on the new-ticket view.
        $tickets = [];
        $isNewView = true;
        $selectedTicketId = $request->query->getInt('ticket_id');
        $activeTicket = null;
        $ticketMessages = [];

        $notice = null;
        if ($request->isMethod('POST')) {
            $notice = 'UI preview only — this support form accepted your message, but tickets are not saved yet.';
        }

        return $this->render('contact/index.html.twig', [
            'tickets' => $tickets,
            'activeTicket' => $activeTicket,
            'ticketMessages' => $ticketMessages,
            'selectedTicketId' => $selectedTicketId,
            'isNewView' => $isNewView,
            'notice' => $notice,
        ]);
    }
}
