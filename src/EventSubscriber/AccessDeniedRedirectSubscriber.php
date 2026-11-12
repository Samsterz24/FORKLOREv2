<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

final class AccessDeniedRedirectSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private Security $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 5],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof InsufficientAuthenticationException || $exception instanceof AuthenticationException) {
            return;
        }

        if (!$exception instanceof AccessDeniedException && !$exception instanceof AccessDeniedHttpException) {
            return;
        }

        // Let anonymous users follow the normal security entry point (e.g. redirect to login).
        if ($this->security->getUser() === null) {
            return;
        }

        $request = $event->getRequest();
        $accept = (string) $request->headers->get('Accept', '');
        $isJson = $request->isXmlHttpRequest() || str_contains($accept, 'application/json');

        if ($isJson) {
            $event->setResponse(new JsonResponse(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN));
            return;
        }

        $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_oops')));
    }
}
