<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\StaffWarning;
use App\Entity\SupportTicket;
use App\Entity\User;
use App\Repository\StaffWarningRepository;
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
final class StaffController extends AbstractController
{
    #[Route('', name: 'staff_dashboard', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        SupportTicketRepository $ticketRepository,
        StaffWarningRepository $warningRepository,
    ): Response {
        $staff = $this->getUser();
        if (!$staff instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $notice = null;
        $error = null;

        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', '');
            $token = (string) $request->request->get('_token', '');

            if ($action === 'toggle_ban') {
                $userId = (int) $request->request->get('user_id', 0);
                $target = $em->getRepository(User::class)->find($userId);
                if (!$this->isCsrfTokenValid('staff_toggle_ban_' . $userId, $token)) {
                    $error = 'Invalid CSRF token.';
                } elseif (!$target) {
                    $error = 'User not found.';
                } elseif ($target->getId() === $staff->getId()) {
                    $error = 'You cannot change your own status.';
                } elseif ($this->isGranted('ROLE_ADMIN', $target)) {
                    $error = 'You cannot ban an admin.';
                } else {
                    $target->setStatus($target->isBanned() ? 'active' : 'banned');
                    $em->flush();
                    $notice = 'User status updated for ' . $target->getUsername() . '.';
                }
            } elseif ($action === 'warn_user') {
                $userId = (int) $request->request->get('target_user_id', 0);
                $reason = trim((string) $request->request->get('reason', ''));
                $target = $em->getRepository(User::class)->find($userId);
                if (!$this->isCsrfTokenValid('staff_warn_user', $token)) {
                    $error = 'Invalid CSRF token.';
                } elseif (!$target) {
                    $error = 'User not found.';
                } elseif ($reason === '') {
                    $error = 'Please provide a warning reason.';
                } else {
                    $warning = new StaffWarning();
                    $warning->setStaff($staff);
                    $warning->setTargetUser($target);
                    $warning->setReason($reason);
                    $em->persist($warning);
                    $em->flush();
                    $notice = 'Warning recorded for ' . $target->getUsername() . '.';
                }
            }

            if ($request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept', ''), 'application/json')) {
                if ($error) {
                    return new JsonResponse(['ok' => false, 'error' => $error], 400);
                }
                return new JsonResponse(['ok' => true, 'notice' => $notice]);
            }
        }

        $data = $this->buildDashboardData($em, $ticketRepository, $warningRepository);

        return $this->render('staff/index.html.twig', $data + [
            'notice' => $notice,
            'error' => $error,
        ]);
    }

    #[Route('/dashboard-data', name: 'staff_dashboard_data', methods: ['GET'])]
    public function data(
        EntityManagerInterface $em,
        SupportTicketRepository $ticketRepository,
        StaffWarningRepository $warningRepository,
    ): JsonResponse {
        $data = $this->buildDashboardData($em, $ticketRepository, $warningRepository);
        $users = array_map(static fn(User $u): array => [
            'id' => $u->getId(),
            'username' => $u->getUsername(),
            'email' => $u->getEmail(),
            'role' => $u->getRole(),
            'status' => $u->getStatus(),
            'banned' => $u->isBanned(),
        ], $data['users']);
        $tickets = array_map(static function ($t): array {
            $u = $t->getUser();
            return [
                'id' => $t->getId(),
                'subject' => $t->getSubject(),
                'status' => $t->getStatus(),
                'user' => $u->getUsername(),
            ];
        }, $data['recentTickets']);
        $warnings = array_map(static function (StaffWarning $w): array {
            return [
                'id' => $w->getId(),
                'target' => $w->getTargetUser()->getUsername(),
                'targetUsername' => $w->getTargetUser()->getUsername(),
                'staff' => $w->getStaff()->getUsername(),
                'createdAt' => $w->getCreatedAt()->format('M d, Y H:i'),
                'reason' => $w->getReason(),
            ];
        }, $data['recentWarnings']);

        return new JsonResponse([
            'openTickets' => $data['openTickets'],
            'users' => $users,
            'recentTickets' => $tickets,
            'recentWarnings' => $warnings,
        ]);
    }

    /**
     * @return array{users: User[], openTickets: int, recentTickets: SupportTicket[], recentWarnings: StaffWarning[]}
     */
    private function buildDashboardData(
        EntityManagerInterface $em,
        SupportTicketRepository $ticketRepository,
        StaffWarningRepository $warningRepository,
    ): array {
        $users = $em->getRepository(User::class)->findBy([], ['username' => 'ASC']);
        $openTickets = $em->getRepository(SupportTicket::class)->count(['status' => 'open'])
            + $em->getRepository(SupportTicket::class)->count(['status' => 'in_progress']);
        $recentTickets = $ticketRepository->findBy([], ['updatedAt' => 'DESC'], 8);
        $recentWarnings = $warningRepository->findBy([], ['createdAt' => 'DESC'], 20);

        return [
            'users' => $users,
            'openTickets' => $openTickets,
            'recentTickets' => $recentTickets,
            'recentWarnings' => $recentWarnings,
        ];
    }
}
