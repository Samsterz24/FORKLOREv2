<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;

final class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
    ) {}

    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function login(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        return $this->render('auth/login.html.twig', [
            'notice' => null,
            'submittedUsername' => '',
        ]);
    }

    #[Route('/register', name: 'register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserAuthenticatorInterface $authenticator, FormLoginAuthenticator $formAuthenticator): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $notice = null;
        $submittedUsername = '';
        $submittedEmail = '';

        if ($request->isMethod('POST')) {
            $submittedUsername = trim((string) $request->request->get('username', ''));
            $submittedEmail = trim((string) $request->request->get('email', ''));
            $password = (string) $request->request->get('password', '');

            if ($submittedUsername !== '' && $submittedEmail !== '' && $password !== '') {
                $user = new User();
                $user->setUsername($submittedUsername);
                $user->setEmail($submittedEmail);
                $user->setPassword($this->hasher->hashPassword($user, $password));
                $this->em->persist($user);
                $this->em->flush();

                $authenticator->authenticateUser($user, $formAuthenticator, $request);

                return $this->redirectToRoute('home');
            }

            $notice = 'Please fill in all fields.';
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