<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class UserController extends AbstractController
{
    #[Route('/users', name: 'admin_users', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $notice = null;
        $error = null;

        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', '');
            $userId = (int) $request->request->get('user_id', 0);
            $token = (string) $request->request->get('_token', '');
            $target = $userId > 0 ? $em->getRepository(User::class)->find($userId) : null;
            $currentUser = $this->getUser();
            $currentId = $currentUser instanceof User ? $currentUser->getId() : null;

            if (!$target) {
                $error = 'User not found.';
            } elseif (!$this->isCsrfTokenValid('admin_user_' . $action . '_' . $userId, $token)) {
                $error = 'Invalid CSRF token. Please try again.';
            } elseif ($action === 'toggle_status') {
                if ($currentId !== null && $target->getId() === $currentId) {
                    $error = 'You cannot change your own account status.';
                } else {
                    $target->setStatus($target->getStatus() === 'banned' ? 'active' : 'banned');
                    $em->flush();
                    $notice = sprintf('Status updated for %s.', $target->getUsername());
                }
            } elseif ($action === 'update_role') {
                $newRole = (string) $request->request->get('role', 'user');
                $map = [
                    'user' => 'ROLE_USER',
                    'staff' => 'ROLE_STAFF',
                    'admin' => 'ROLE_ADMIN',
                ];
                if ($currentId !== null && $target->getId() === $currentId) {
                    $error = 'You cannot change your own role.';
                } elseif (!isset($map[$newRole])) {
                    $error = 'Invalid role.';
                } else {
                    $target->setRole($map[$newRole]);
                    $em->flush();
                    $notice = sprintf('Role updated for %s.', $target->getUsername());
                }
            } else {
                $error = 'Unknown action.';
            }
        }

        $users = $em->getRepository(User::class)->findBy([], ['id' => 'ASC']);
        $viewUsers = array_map(static function (User $user): array {
            $role = match ($user->getRole()) {
                'ROLE_ADMIN' => 'admin',
                'ROLE_STAFF' => 'staff',
                default => 'user',
            };

            return [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'role' => $role,
                'status' => $user->getStatus(),
            ];
        }, $users);

        $currentUser = $this->getUser();
        $currentAdminId = $currentUser instanceof User ? $currentUser->getId() : null;

        return $this->render('admin/users.html.twig', [
            'users' => $viewUsers,
            'currentAdminId' => $currentAdminId,
            'notice' => $notice,
            'error' => $error,
        ]);
    }
}
