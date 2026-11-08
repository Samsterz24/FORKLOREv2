<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function login(Request $request): Response
    {
        $notice = null;
        $submittedUsername = '';

        if ($request->isMethod('POST')) {
            $submittedUsername = trim((string) $request->request->get('username', ''));
            $notice = 'UI preview only — authentication is not wired up yet.';
        }

        return $this->render('auth/login.html.twig', [
            'notice' => $notice,
            'submittedUsername' => $submittedUsername,
        ]);
    }

    #[Route('/register', name: 'register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        $notice = null;
        $submittedUsername = '';
        $submittedEmail = '';

        if ($request->isMethod('POST')) {
            $submittedUsername = trim((string) $request->request->get('username', ''));
            $submittedEmail = trim((string) $request->request->get('email', ''));
            $notice = 'UI preview only — account creation is not wired up yet.';
        }

        return $this->render('auth/register.html.twig', [
            'notice' => $notice,
            'submittedUsername' => $submittedUsername,
            'submittedEmail' => $submittedEmail,
        ]);
    }

    #[Route('/logout', name: 'logout', methods: ['GET'])]
    public function logout(): Response
    {
        return $this->redirectToRoute('home');
    }
}
