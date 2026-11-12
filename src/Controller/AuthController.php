<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;

final class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
    ) {}

    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        $bannedNotice = $request->query->get('banned') === '1';

        return $this->render('auth/login.html.twig', [
            'notice' => null,
            'submittedUsername' => $lastUsername ?: '',
            'error' => $error,
            'bannedNotice' => $bannedNotice,
        ]);
    }

    #[Route('/register', name: 'register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserAuthenticatorInterface $authenticator, #[Autowire(service: 'security.authenticator.form_login.main')] FormLoginAuthenticator $formAuthenticator): Response
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

    #[Route('/session/status', name: 'session_status', methods: ['GET'])]
    public function sessionStatus(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['authenticated' => false, 'banned' => false]);
        }

        // Refresh from DB to detect ban changes during active session
        $fresh = $this->em->getRepository(User::class)->find($user->getId());
        if (!$fresh || $fresh->isBanned()) {
            // Force logout server-side
            $this->container->get('security.token_storage')->setToken(null);
            if ($request->hasSession()) {
                $session = $request->getSession();
                $session->invalidate();
            }
            return $this->json(['authenticated' => false, 'banned' => true]);
        }

        return $this->json(['authenticated' => true, 'banned' => false]);
    }
}