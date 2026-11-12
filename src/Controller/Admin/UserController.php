<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class UserController extends AbstractController
{
    public function __construct(
        #[Autowire('%app.protected_admin_username%')]
        private readonly string $protectedAdminUsername = '',
    ) {
    }

    private function isProtectedAdmin(User $user): bool
    {
        return $this->protectedAdminUsername !== '' && $user->getUsername() === $this->protectedAdminUsername;
    }

    #[Route('/users', name: 'admin_users', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
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

            $tokenId = $action === 'create_user' ? 'admin_user_create' : ('admin_user_' . $action . '_' . $userId);
            if (!$this->isCsrfTokenValid($tokenId, $token)) {
                $error = 'Invalid CSRF token. Please try again.';
            } elseif ($action === 'create_user') {
                [$error, $notice] = $this->handleCreateUser($request, $em, $hasher);
            } elseif (!$target) {
                $error = 'User not found.';
            } elseif ($action === 'toggle_status') {
                if ($this->isProtectedAdmin($target)) {
                    $error = 'The protected admin account cannot be banned or disabled.';
                } elseif ($currentId !== null && $target->getId() === $currentId) {
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
            } elseif ($action === 'update_user') {
                [$error, $notice] = $this->handleUpdateUser($request, $em, $target);
            } elseif ($action === 'reset_password') {
                [$error, $notice] = $this->handleResetPassword($request, $hasher, $em, $target);
            } elseif ($action === 'delete_user') {
                if ($this->isProtectedAdmin($target)) {
                    $error = 'The protected admin account cannot be deleted.';
                } elseif ($currentId !== null && $target->getId() === $currentId) {
                    $error = 'You cannot delete your own account.';
                } else {
                    $username = $target->getUsername();
                    $em->remove($target);
                    $em->flush();
                    $notice = sprintf('User %s deleted.', $username);
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
                'createdAt' => $user->getCreatedAt(),
            ];
        }, $users);

        $currentUser = $this->getUser();
        $currentAdminId = $currentUser instanceof User ? $currentUser->getId() : null;

        return $this->render('admin/users.html.twig', [
            'users' => $viewUsers,
            'currentAdminId' => $currentAdminId,
            'protectedAdminUsername' => $this->protectedAdminUsername,
            'notice' => $notice,
            'error' => $error,
        ]);
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function handleCreateUser(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): array
    {
        $username = trim((string) $request->request->get('username', ''));
        $email = trim((string) $request->request->get('email', ''));
        $password = (string) $request->request->get('password', '');
        $roleKey = (string) $request->request->get('role', 'user');
        $status = (string) $request->request->get('status', 'active');
        $map = ['user' => 'ROLE_USER', 'staff' => 'ROLE_STAFF', 'admin' => 'ROLE_ADMIN'];

        if ($username === '' || $email === '' || $password === '') {
            return ['Please provide username, email, and password.', null];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['Please provide a valid email address.', null];
        }
        if (mb_strlen($password) < 6) {
            return ['Password must be at least 6 characters.', null];
        }
        if (!isset($map[$roleKey])) {
            return ['Invalid role.', null];
        }
        if (!in_array($status, ['active', 'banned'], true)) {
            $status = 'active';
        }

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setRole($map[$roleKey]);
        $user->setStatus($status);
        $user->setPassword($hasher->hashPassword($user, $password));

        $em->persist($user);
        try {
            $em->flush();
        } catch (UniqueConstraintViolationException) {
            return ['Username or email already exists.', null];
        }

        return [null, sprintf('User %s created.', $username)];
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function handleUpdateUser(Request $request, EntityManagerInterface $em, User $target): array
    {
        $username = trim((string) $request->request->get('username', $target->getUsername()));
        $email = trim((string) $request->request->get('email', $target->getEmail()));

        if ($username === '' || $email === '') {
            return ['Username and email are required.', null];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['Please provide a valid email address.', null];
        }

        $target->setUsername($username);
        $target->setEmail($email);
        try {
            $em->flush();
        } catch (UniqueConstraintViolationException) {
            return ['Username or email already exists.', null];
        }

        return [null, sprintf('User %s updated.', $target->getUsername())];
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function handleResetPassword(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $em, User $target): array
    {
        $password = (string) $request->request->get('new_password', '');
        if (mb_strlen($password) < 6) {
            return ['New password must be at least 6 characters.', null];
        }

        $target->setPassword($hasher->hashPassword($target, $password));
        $em->flush();

        return [null, sprintf('Password reset for %s.', $target->getUsername())];
    }
}
