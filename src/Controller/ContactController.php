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

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        SupportTicketRepository $ticketRepository,
        SupportMessageRepository $messageRepository,
    ): Response {
        $user = $this->getUser();
        $tickets = [];
        $activeTicket = null;
        $ticketMessages = [];
        $selectedTicketId = $request->query->getInt('ticket_id');
        $isNewView = $request->query->getBoolean('new', false) || $selectedTicketId <= 0;
        $notice = null;
        $error = null;

        if ($user instanceof User) {
            $tickets = $ticketRepository->findForUser($user);

            if ($request->isMethod('POST')) {
                $action = (string) $request->request->get('action', '');
                if ($action === 'create_ticket') {
                    $subject = trim((string) $request->request->get('subject', ''));
                    $messageText = trim((string) $request->request->get('message', ''));
                    $token = (string) $request->request->get('_token', '');
                    if (!$this->isCsrfTokenValid('contact_create_ticket', $token)) {
                        $error = 'Invalid CSRF token. Please try again.';
                    } elseif ($subject === '' || $messageText === '') {
                        $error = 'Please provide a subject and message.';
                        $isNewView = true;
                    } else {
                        $ticket = new SupportTicket();
                        $ticket->setUser($user);
                        $ticket->setSubject($subject);
                        $ticket->setStatus('open');
                        $message = new SupportMessage();
                        $message->setTicket($ticket);
                        $message->setSender($user);
                        $message->setMessage($messageText);
                        $message->setIsAdmin(false);
                        $ticket->addMessage($message);
                        $ticket->setUpdatedAt(new \DateTimeImmutable());
                        $em->persist($ticket);
                        $em->persist($message);
                        $em->flush();

                        return $this->redirectToRoute('contact', ['ticket_id' => $ticket->getId()]);
                    }
                } elseif ($action === 'send_reply') {
                    $ticketId = (int) $request->request->get('ticket_id', 0);
                    $messageText = trim((string) $request->request->get('message', ''));
                    $token = (string) $request->request->get('_token', '');
                    $ticket = $ticketRepository->find($ticketId);
                    if (!$ticket || $ticket->getUser()->getId() !== $user->getId()) {
                        $error = 'Ticket not found.';
                        if ($request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept', ''), 'application/json')) {
                            return new JsonResponse(['ok' => false, 'error' => $error], 404);
                        }
                    } elseif (!$this->isCsrfTokenValid('contact_reply_' . $ticketId, $token)) {
                        $error = 'Invalid CSRF token. Please try again.';
                        $selectedTicketId = $ticketId;
                        $isNewView = false;
                        if ($request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept', ''), 'application/json')) {
                            return new JsonResponse(['ok' => false, 'error' => $error], 400);
                        }
                    } elseif ($messageText === '') {
                        $error = 'Please enter a message.';
                        $selectedTicketId = $ticketId;
                        $isNewView = false;
                        if ($request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept', ''), 'application/json')) {
                            return new JsonResponse(['ok' => false, 'error' => $error], 400);
                        }
                    } elseif ($ticket->getStatus() === 'closed') {
                        $error = 'This ticket is closed. You cannot send new messages.';
                        $selectedTicketId = $ticketId;
                        $isNewView = false;
                        if ($request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept', ''), 'application/json')) {
                            return new JsonResponse(['ok' => false, 'error' => $error, 'status' => 'closed'], 400);
                        }
                    } else {
                        $message = new SupportMessage();
                        $message->setTicket($ticket);
                        $message->setSender($user);
                        $message->setMessage($messageText);
                        $message->setIsAdmin(false);
                        $ticket->addMessage($message);
                        $ticket->setUpdatedAt(new \DateTimeImmutable());
                        $em->persist($message);
                        $em->flush();

                        if ($request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept', ''), 'application/json')) {
                            return new JsonResponse([
                                'ok' => true,
                                'status' => $ticket->getStatus(),
                                'message' => [
                                    'id' => $message->getId(),
                                    'isAdmin' => false,
                                    'sender' => $user->getUsername(),
                                    'createdAt' => $message->getCreatedAt()->format('M d, h:i A'),
                                    'message' => $message->getMessage(),
                                ],
                            ]);
                        }

                        return $this->redirectToRoute('contact', ['ticket_id' => $ticket->getId()]);
                    }
                }
            }

            if ($selectedTicketId > 0) {
                foreach ($tickets as $t) {
                    if ($t->getId() === $selectedTicketId) {
                        $activeTicket = $t;
                        $isNewView = false;
                        break;
                    }
                }
                if (!$activeTicket) {
                    $isNewView = true;
                }
            } elseif (!$request->query->has('new') && count($tickets) > 0) {
                // default to new view unless explicitly none; keep new ticket UX clear
                $isNewView = true;
            }

            if ($activeTicket) {
                $ticketMessages = $messageRepository->findForTicket($activeTicket);
            }
        } else {
            if ($request->isMethod('POST')) {
                $error = 'Please log in to send a support ticket.';
            }
            $isNewView = true;
        }

        $viewTickets = [];
        foreach ($tickets as $t) {
            $last = $messageRepository->findLastForTicket($t);
            $viewTickets[] = [
                'id' => $t->getId(),
                'subject' => $t->getSubject(),
                'status' => $t->getStatus(),
                'createdAt' => $t->getCreatedAt(),
                'lastMessage' => $last ? $last->getMessage() : '',
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

        $viewActive = null;
        if ($activeTicket) {
            $viewActive = [
                'id' => $activeTicket->getId(),
                'subject' => $activeTicket->getSubject(),
                'status' => $activeTicket->getStatus(),
                'createdAt' => $activeTicket->getCreatedAt(),
            ];
        }

        return $this->render('contact/index.html.twig', [
            'tickets' => $viewTickets,
            'activeTicket' => $viewActive,
            'ticketMessages' => $viewMessages,
            'selectedTicketId' => $activeTicket ? $activeTicket->getId() : 0,
            'isNewView' => $isNewView || !$activeTicket,
            'notice' => $notice,
            'error' => $error,
        ]);
    }

    #[Route('/contact/tickets/{id}/messages', name: 'contact_ticket_messages', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function messages(
        int $id,
        Request $request,
        SupportTicketRepository $ticketRepository,
        SupportMessageRepository $messageRepository,
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $ticket = $ticketRepository->find($id);
        if (!$ticket || $ticket->getUser()->getId() !== $user->getId()) {
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
                'createdAt' => $m->getCreatedAt()->format('M d, h:i A'),
                'message' => $m->getMessage(),
            ];
        }

        return new JsonResponse([
            'status' => $ticket->getStatus(),
            'messages' => $out,
        ]);
    }
}
