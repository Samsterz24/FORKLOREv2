<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class UserController extends AbstractController
{
    #[Route('/users', name: 'admin_users', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $notice = null;

        if ($request->isMethod('POST')) {
            $notice = 'UI preview only — role and status controls accepted your input, but no account was changed.';
        }

        // The user list and current administrator will be loaded from the
        // database once authentication and persistence are implemented.
        return $this->render('admin/users.html.twig', [
            'users' => [],
            'currentAdminId' => null,
            'notice' => $notice,
        ]);
    }
}
