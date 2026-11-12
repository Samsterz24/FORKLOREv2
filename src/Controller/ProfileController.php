<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Favorite;
use App\Entity\Recipe;
use App\Entity\Story;
use App\Entity\User;
use App\Repository\StorySaveRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/profile', name: 'profile', methods: ['GET', 'POST'])]
    public function index(Request $request, StorySaveRepository $storySaveRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            $tab = (string) $request->query->get('tab', 'recipes');
            return $this->render('profile/index.html.twig', [
                'profile' => null,
                'isOwner' => false,
                'tab' => in_array($tab, ['recipes', 'saved', 'stories'], true) ? $tab : 'recipes',
                'uploadedRecipes' => [],
                'savedRecipes' => [],
                'savedStories' => [],
                'submittedStories' => [],
                'notice' => null,
                'error' => null,
            ]);
        }

        return $this->showProfile($request, $user, true, $storySaveRepository);
    }

    #[Route('/profile/{username}', name: 'profile_show', methods: ['GET'], requirements: ['username' => '[A-Za-z0-9_.-]+'])]
    public function show(string $username, Request $request, StorySaveRepository $storySaveRepository): Response
    {
        $profile = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
        if (!$profile) {
            throw $this->createNotFoundException('User not found.');
        }

        $current = $this->getUser();
        $isOwner = $current instanceof User && $current->getId() === $profile->getId();

        return $this->showProfile($request, $profile, $isOwner, $storySaveRepository);
    }

    #[Route('/profile/recipe/{id}/visibility', name: 'profile_recipe_visibility', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleRecipeVisibility(int $id, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $recipe = $this->em->getRepository(Recipe::class)->find($id);
        $token = (string) $request->request->get('_token', '');
        if (!$recipe || $recipe->getUser()->getId() !== $user->getId()) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('recipe_visibility_' . $recipe->getId(), $token)) {
            $this->addFlash('error', 'Invalid CSRF token.');
        } else {
            $recipe->setIsPublic(!$recipe->isPublic());
            $this->em->flush();
        }

        return $this->redirectToRoute('profile', ['tab' => 'recipes']);
    }

    private function showProfile(Request $request, User $profile, bool $isOwner, StorySaveRepository $storySaveRepository): Response
    {
        $tab = (string) $request->query->get('tab', 'recipes');
        if (!in_array($tab, ['recipes', 'saved', 'stories'], true)) {
            $tab = 'recipes';
        }
        if (!$isOwner && $tab === 'saved') {
            $tab = 'recipes';
        }

        $notice = null;
        $error = null;
        if ($isOwner && $request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'update_profile');
            if ($action === 'change_password') {
                // handled in changePassword route; keep compatibility no-op here
            } else {
                $bio = trim((string) $request->request->get('bio', ''));
                $profile->setBio($bio !== '' ? $bio : null);
                $this->em->flush();
                $notice = 'Profile updated.';
            }
        }

        $uploadedRecipes = $this->em->getRepository(Recipe::class)->findBy(
            $isOwner ? ['user' => $profile] : ['user' => $profile, 'isPublic' => true],
            ['createdAt' => 'DESC']
        );
        $submittedStories = $this->em->getRepository(Story::class)->findBy(['user' => $profile], ['createdAt' => 'DESC']);

        $savedRecipes = [];
        $savedStories = [];
        if ($isOwner) {
            $favorites = $this->em->getRepository(Favorite::class)->findBy(['user' => $profile], ['createdAt' => 'DESC']);
            foreach ($favorites as $fav) {
                $savedRecipes[] = $fav->getRecipe();
            }
            $savedStories = $storySaveRepository->findStoriesSavedByUser($profile);
        }

        return $this->render('profile/index.html.twig', [
            'profile' => $profile,
            'isOwner' => $isOwner,
            'tab' => $tab,
            'uploadedRecipes' => $uploadedRecipes,
            'savedRecipes' => $savedRecipes,
            'savedStories' => $savedStories,
            'submittedStories' => $submittedStories,
            'notice' => $notice,
            'error' => $error,
        ]);
    }

    #[Route('/profile/change-password', name: 'profile_change_password', methods: ['POST'])]
    public function changePassword(Request $request, UserPasswordHasherInterface $hasher): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('profile_change_password', $token)) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('profile');
        }

        $currentPassword = (string) $request->request->get('current_password', '');
        $newPassword = (string) $request->request->get('new_password', '');
        $confirmPassword = (string) $request->request->get('confirm_password', '');

        if (!$hasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('error', 'Current password is incorrect.');
            return $this->redirectToRoute('profile');
        }
        if (mb_strlen($newPassword) < 6) {
            $this->addFlash('error', 'New password must be at least 6 characters.');
            return $this->redirectToRoute('profile');
        }
        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'New password and confirmation do not match.');
            return $this->redirectToRoute('profile');
        }

        $user->setPassword($hasher->hashPassword($user, $newPassword));
        $this->em->flush();
        $this->addFlash('success', 'Password changed successfully.');

        return $this->redirectToRoute('profile');
    }
}
