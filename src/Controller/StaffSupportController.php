<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SupportMessage;
use App\Entity\SupportTicket;
use App\Entity\User;
use App\Repository\SupportMessageRepository;
use App\Repository\SupportTicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff')]
#[IsGranted('ROLE_STAFF')]
final class StaffSupportController extends AbstractController
{
    private const STATUSES = ['all', 'open', 'in_progress', 'closed'];

    #[Route('/support', name: 'staff_support', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        SupportTicketRepository $ticketRepository,
        SupportMessageRepository $messageRepository,
    ): Response {
        $status = (string) $request->query->get('status', 'all');
        if (!in_array($status, self::STATUSES, true)) {
            $status = 'all';
        }

        $notice = null;
        $error = null;

        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', '');
            $ticketId = (int) $request->request->get('ticket_id', 0);
            $ticket = $ticketRepository->find($ticketId);
            $token = (string) $request->request->get('_token', '');

            if (!$ticket) {
                $error = 'Ticket not found.';
            } elseif ($action === 'send_reply') {
                $messageText = trim((string) $request->request->get('message', ''));
                if (!$this->isCsrfTokenValid('staff_support_reply_' . $ticketId, $token)) {
                    $error = 'Invalid CSRF token. Please try again.';
                } elseif ($messageText === '') {
                    $error = 'Please enter a reply.';
                } elseif ($ticket->getStatus() === 'closed') {
                    $error = 'This ticket is closed. Change its status to reply.';
                } else {
                    $staff = $this->getUser();
                    if (!$staff instanceof User) {
                        throw $this->createAccessDeniedException();
                    }
                    $message = new SupportMessage();
                    $message->setTicket($ticket);
                    $message->setSender($staff);
                    $message->setMessage($messageText);
                    $message->setIsAdmin(true);
                    $ticket->addMessage($message);
                    if ($ticket->getStatus() === 'open') {
                        $ticket->setStatus('in_progress');
                    }
                    $ticket->setUpdatedAt(new \DateTimeImmutable());
                    $em->persist($message);
                    $em->flush();

                    if ($request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept', ''), 'application/json')) {
                        return new JsonResponse([
                            'ok' => true,
                            'status' => $ticket->getStatus(),
                            'message' => [
                                'id' => $message->getId(),
                                'isAdmin' => true,
                                'sender' => $staff->getUsername(),
                                'createdAt' => $message->getCreatedAt()->format('M d, H:i'),
                                'message' => $message->getMessage(),
                            ],
                        ]);
                    }

                    return $this->redirectToRoute('staff_support', ['status' => $status, 'ticket_id' => $ticket->getId()]);
                }
            } elseif ($action === 'update_status') {
                $newStatus = (string) $request->request->get('status', '');
                if (!$this->isCsrfTokenValid('staff_support_status_' . $ticketId, $token)) {
                    $error = 'Invalid CSRF token. Please try again.';
                } elseif (!in_array($newStatus, ['open', 'in_progress', 'closed'], true)) {
                    $error = 'Invalid status.';
                } else {
                    $ticket->setStatus($newStatus);
                    $ticket->setUpdatedAt(new \DateTimeImmutable());
                    $em->flush();
                    if ($request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept', ''), 'application/json')) {
                        return new JsonResponse(['ok' => true, 'status' => $ticket->getStatus()]);
                    }
                    return $this->redirectToRoute('staff_support', ['status' => $status, 'ticket_id' => $ticket->getId()]);
                }
            }

            if ($request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept', ''), 'application/json')) {
                return new JsonResponse(['ok' => false, 'error' => $error ?: 'Request failed.'], 400);
            }
        }

        $selectedTicketId = $request->query->getInt('ticket_id', 0);
        $tickets = $ticketRepository->findForAdmin($status === 'all' ? null : $status);

        $viewTickets = [];
        foreach ($tickets as $t) {
            $last = $messageRepository->findLastForTicket($t);
            $u = $t->getUser();
            $viewTickets[] = [
                'id' => $t->getId(),
                'subject' => $t->getSubject(),
                'status' => $t->getStatus(),
                'createdAt' => $t->getCreatedAt(),
                'lastMessage' => $last ? $last->getMessage() : '',
                'user' => $u->getUsername(),
                'email' => $u->getEmail(),
            ];
        }

        $activeTicket = null;
        $ticketMessages = [];
        if ($selectedTicketId > 0) {
            $active = $ticketRepository->find($selectedTicketId);
            if ($active) {
                $activeTicket = $active;
                $ticketMessages = $messageRepository->findForTicket($active);
            }
        }
        if (!$activeTicket && count($tickets) > 0) {
            $activeTicket = $tickets[0];
            $selectedTicketId = (int) $activeTicket->getId();
            $ticketMessages = $messageRepository->findForTicket($activeTicket);
        }

        $viewActive = null;
        if ($activeTicket) {
            $u = $activeTicket->getUser();
            $viewActive = [
                'id' => $activeTicket->getId(),
                'subject' => $activeTicket->getSubject(),
                'status' => $activeTicket->getStatus(),
                'createdAt' => $activeTicket->getCreatedAt(),
                'user' => $u->getUsername(),
                'email' => $u->getEmail(),
            ];
        }

        $viewMessages = [];
        foreach ($ticketMessages as $m) {
            $viewMessages[] = [
                'id' => $m->getId(),
                'isAdmin' => $m->isAdmin(),
                'sender' => $m->getSender()->getUsername(),
                'createdAt' => $m->getCreatedAt(),
                'message' => $m->getMessage(),
            ];
        }

        return $this->render('staff/support.html.twig', [
            'tickets' => $viewTickets,
            'status' => $status,
            'selectedTicketId' => $selectedTicketId,
            'activeTicket' => $viewActive,
            'ticketMessages' => $viewMessages,
            'notice' => $notice,
            'error' => $error,
        ]);
    }

    #[Route('/support/tickets/{id}/messages', name: 'staff_support_ticket_messages', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function messages(
        int $id,
        Request $request,
        SupportTicketRepository $ticketRepository,
        SupportMessageRepository $messageRepository,
    ): JsonResponse {
        $ticket = $ticketRepository->find($id);
        if (!$ticket) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $afterId = $request->query->getInt('after', 0);
        $messages = $messageRepository->findForTicket($ticket);
        $out = [];
        foreach ($messages as $m) {
            if ($afterId > 0 && $m->getId() !== null && $m->getId() <= $afterId) {
                continue;
            }
            $out[] = [
                'id' => $m->getId(),
                'isAdmin' => $m->isAdmin(),
                'sender' => $m->getSender()->getUsername(),
                'createdAt' => $m->getCreatedAt()->format('M d, H:i'),
                'message' => $m->getMessage(),
            ];
        }

        return new JsonResponse([
            'status' => $ticket->getStatus(),
            'messages' => $out,
        ]);
    }
}
