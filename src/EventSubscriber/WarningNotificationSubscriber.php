<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Repository\StaffWarningRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

final class WarningNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private StaffWarningRepository $warningRepository,
        private Environment $twig,
        private \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'onController'];
    }

    public function onController(ControllerEvent $event): void
    {
        $user = null;
        $token = $this->tokenStorage->getToken();
        if ($token) {
            $u = $token->getUser();
            if ($u instanceof User) {
                $user = $u;
            }
        }

        $count = 0;
        if ($user) {
            $count = $this->warningRepository->countUnreadForUser($user);
        }

        $this->twig->addGlobal('unreadWarningCount', $count);
    }
}
