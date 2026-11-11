<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/subscriptions')]
#[IsGranted('ROLE_USER')]
final class SubscriptionController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('', name: 'subscriptions_index', methods: ['GET', 'POST'])]
    public function index(Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('login');
        }

        $errors = [];
        $planType = 'monthly';

        if ($request->isMethod('POST')) {
            $planType = $request->request->get('plan_type') === 'annual' ? 'annual' : 'monthly';
            $token = new CsrfToken('subscribe', (string) $request->request->get('_token'));

            if (!$csrfTokenManager->isTokenValid($token)) {
                $errors[] = 'Invalid or expired form submission. Please try again.';
            }

            $paymentMethod = trim((string) $request->request->get('payment_method', ''));
            $cardName = trim((string) $request->request->get('card_name', ''));
            $cardNumber = preg_replace('/\s+/', '', (string) $request->request->get('card_number', '')) ?? '';
            $cardExpiry = trim((string) $request->request->get('card_expiry', ''));
            $cardCvv = trim((string) $request->request->get('card_cvv', ''));
            $billingZip = trim((string) $request->request->get('billing_zip', ''));

            if ($paymentMethod === '') { $errors[] = 'Please select a payment method.'; }
            if ($cardName === '') { $errors[] = 'Cardholder name is required.'; }
            if (strlen($cardNumber) < 12) { $errors[] = 'Please enter a valid card number.'; }
            if ($cardExpiry === '') { $errors[] = 'Card expiry is required.'; }
            if ($cardCvv === '') { $errors[] = 'CVV is required.'; }
            if ($billingZip === '') { $errors[] = 'Billing ZIP/postal code is required.'; }

            if (!$errors) {
                $months = $planType === 'annual' ? 12 : 1;
                $now = new \DateTimeImmutable();
                $currentExpires = $user->getStampExpiresAt();
                $base = ($currentExpires && $currentExpires > $now) ? $currentExpires : $now;
                $newExpires = $base->modify("+{$months} months");

                $user->setHasFoodieStamp(true);
                $user->setStampExpiresAt($newExpires);

                $subscription = new Subscription();
                $subscription->setUser($user);
                $subscription->setPlan($planType === 'annual' ? 'foodie_yearly' : 'foodie_monthly');
                $subscription->setStatus('active');
                $subscription->setExpiresAt($newExpires);
                $this->em->persist($subscription);

                $this->em->flush();

                $this->addFlash('success', $planType === 'annual'
                    ? 'Lore Foodie Stamp activated (annual). Thanks for supporting ForkLore!'
                    : 'Lore Foodie Stamp activated (monthly). Thanks for supporting ForkLore!');

                return $this->redirectToRoute('subscriptions_index');
            }
        }

        $success = $request->getSession()->getFlashBag()->get('success');
        $successMessage = $success[0] ?? null;

        return $this->render('subscriptions/index.html.twig', [
            'user' => $user,
            'success' => $successMessage,
            'errors' => $errors,
            'planType' => $planType,
        ]);
    }
}