<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
        $tier = 'basic';

        if ($request->isMethod('POST')) {
            $planType = $request->request->get('plan_type') === 'annual' ? 'annual' : 'monthly';
            $tier = in_array($request->request->get('tier', 'basic'), ['basic', 'plus', 'premium'], true)
                ? $request->request->get('tier', 'basic') : 'basic';
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

            $isGcash = $paymentMethod === 'GCash';
            $isCard = $paymentMethod === 'Credit/Debit Card';

            if ($isCard) {
                if ($cardName === '') { $errors[] = 'Cardholder name is required.'; }
                if (strlen($cardNumber) < 12) { $errors[] = 'Please enter a valid card number.'; }
                if ($cardExpiry === '') { $errors[] = 'Card expiry is required.'; }
                if ($cardCvv === '') { $errors[] = 'CVV is required.'; }
                if ($billingZip === '') { $errors[] = 'Billing ZIP/postal code is required.'; }
            }

            $gcashProofFile = null;
            if ($isGcash) {
                /** @var UploadedFile|null $file */
                $file = $request->files->get('gcash_proof');
                if (!$file instanceof UploadedFile || !$file->isValid()) {
                    $errors[] = 'Please upload a screenshot of your GCash transaction.';
                } else {
                    $ext = strtolower((string) pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                        $errors[] = 'Please upload a valid image (JPG, PNG, or WebP).';
                    } else {
                        $gcashProofFile = $file;
                    }
                }
            }

            if (!$errors) {
                $months = $planType === 'annual' ? 12 : 1;
                $now = new \DateTimeImmutable();
                $currentExpires = $user->getStampExpiresAt();
                $base = ($currentExpires && $currentExpires > $now) ? $currentExpires : $now;
                $newExpires = $base->modify("+{$months} months");

                $user->setHasFoodieStamp(true);
                $user->setStampExpiresAt($newExpires);

                $tierLabels = ['basic' => 'Basic', 'plus' => 'Plus', 'premium' => 'Premium'];
                $subscription = new Subscription();
                $subscription->setUser($user);
                $subscription->setPlan('foodie_' . $tier . '_' . ($planType === 'annual' ? 'yearly' : 'monthly'));
                $subscription->setStatus('active');
                $subscription->setExpiresAt($newExpires);

                if ($isGcash && $gcashProofFile instanceof UploadedFile) {
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/gcash';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0775, true);
                    }
                    $ext = strtolower((string) pathinfo($gcashProofFile->getClientOriginalName(), PATHINFO_EXTENSION));
                    if ($ext === '') { $ext = 'jpg'; }
                    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
                    $gcashProofFile->move($uploadDir, $filename);
                    $subscription->setPaymentProof($filename);
                }

                $this->em->persist($subscription);
                $this->em->flush();

                $this->addFlash('success', sprintf(
                    'Lore Foodie %s Stamp activated (%s). Thanks for supporting ForkLore!',
                    $tierLabels[$tier],
                    $planType === 'annual' ? 'annual' : 'monthly'
                ));

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
            'tier' => $tier,
        ]);

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