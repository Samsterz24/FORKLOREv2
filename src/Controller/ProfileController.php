<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Recipe;
use App\Entity\Story;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/profile', name: 'profile', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $tab = (string) $request->query->get('tab', 'recipes');
        if (!in_array($tab, ['recipes', 'saved', 'stories'], true)) {
            $tab = 'recipes';
        }

        $user = $this->getUser();
        $profile = $user instanceof User ? $user : null;

        $notice = null;
        if ($request->isMethod('POST') && $profile) {
            $bio = trim((string) $request->request->get('bio', ''));
            $profile->setBio($bio);
            $this->em->flush();
            $notice = 'Profile updated.';
        }

        $uploadedRecipes = $profile ? $this->em->getRepository(Recipe::class)->findBy(['user' => $profile]) : [];
        $submittedStories = $profile ? $this->em->getRepository(Story::class)->findBy(['user' => $profile]) : [];
        $savedRecipes = [];
        if ($profile) {
            $favorites = $this->em->getRepository(\App\Entity\Favorite::class)->findBy(['user' => $profile]);
            foreach ($favorites as $fav) {
                $savedRecipes[] = $fav->getRecipe();
            }
        }

        return $this->render('profile/index.html.twig', [
            'profile' => $profile,
            'tab' => $tab,
            'uploadedRecipes' => $uploadedRecipes,
            'savedRecipes' => $savedRecipes,
            'submittedStories' => $submittedStories,
            'notice' => $notice,
        ]);
    }
}