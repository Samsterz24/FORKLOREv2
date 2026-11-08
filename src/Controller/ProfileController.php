<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $tab = (string) $request->query->get('tab', 'recipes');

        if (!in_array($tab, ['recipes', 'saved', 'stories'], true)) {
            $tab = 'recipes';
        }

        $notice = null;
        if ($request->isMethod('POST')) {
            $notice = 'UI preview only — your profile changes were accepted visually, but they are not saved yet.';
        }

        // The authenticated user and their collections will be loaded from the
        // database in the persistence phase. Until then the view shows empty states.
        return $this->render('profile/index.html.twig', [
            'profile' => null,
            'tab' => $tab,
            'uploadedRecipes' => [],
            'savedRecipes' => [],
            'submittedStories' => [],
            'notice' => $notice,
        ]);
    }
}
