<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class BannedUserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 7]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Avoid loops and allow login/logout/profiler/assets
        if (str_starts_with($path, '/_profiler') || str_starts_with($path, '/_wdt') || str_starts_with($path, '/assets') || str_starts_with($path, '/build')) {
            return;
        }
        $route = (string) $request->attributes->get('_route', '');
        if (in_array($route, ['login', 'logout', 'session_status'], true) || $path === '/login' || $path === '/logout' || $path === '/session/status') {
            return;
        }

        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isBanned()) {
            return;
        }

        // User became banned during an active session: force logout and show notice
        $this->tokenStorage->setToken(null);
        if ($request->hasSession()) {
            $session = $request->getSession();
            $session->invalidate();
        }

        $event->setResponse(new RedirectResponse($this->urlGenerator->generate('login', ['banned' => 1])));
    }
}
