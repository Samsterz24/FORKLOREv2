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

        if ($request->isMethod('POST')) {
            $notice = 'UI preview only — role and status controls accepted your input, but no account was changed.';
        }

        $users = $em->getRepository(User::class)->findBy([], ['id' => 'ASC']);
        $viewUsers = array_map(static function (User $user): array {
            $role = match ($user->getRole()) {
                'ROLE_ADMIN' => 'admin',
                'ROLE_MODERATOR' => 'moderator',
                default => 'user',
            };

            return [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'role' => $role,
                'status' => 'active',
            ];
        }, $users);

        $currentUser = $this->getUser();
        $currentAdminId = $currentUser instanceof User ? $currentUser->getId() : null;

        return $this->render('admin/users.html.twig', [
            'users' => $viewUsers,
            'currentAdminId' => $currentAdminId,
            'notice' => $notice,
        ]);
    }
}
