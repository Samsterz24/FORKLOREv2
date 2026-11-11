<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/subscriptions')]
#[IsGranted('ROLE_USER')]
final class SubscriptionController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('', name: 'subscriptions_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('login');
        }

        $success = null;
        $errors = [];
        $planType = 'monthly';

        if ($request->isMethod('POST')) {
            $planType = $request->request->get('plan_type') === 'annual' ? 'annual' : 'monthly';
            $months = $planType === 'annual' ? 12 : 1;
            $expires = $user->getStampExpiresAt();
            if ($expires && $expires > new \DateTimeImmutable()) {
                $user->setStampExpiresAt($expires->modify("+{$months} months"));
            } else {
                $user->setStampExpiresAt((new \DateTimeImmutable())->modify("+{$months} months"));
            }
            $user->setHasFoodieStamp(true);
            $this->em->flush();
            $success = 'Subscription activated!';
        }

        return $this->render('subscriptions/index.html.twig', [
            'user' => $user,
            'success' => $success,
            'errors' => $errors,
            'planType' => $planType,
        ]);
    }
}